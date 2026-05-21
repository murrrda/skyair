<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── Disjointness trigger functions ──────────────────────────────────────
        // Each fires BEFORE INSERT on its own table and checks the sibling table.
        // SQLSTATE '23P01' = exclusion_violation (PostgreSQL-specific integrity class).

        DB::unprepared("
            CREATE OR REPLACE FUNCTION guard_putnik_not_zaposlen()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS \$\$
            DECLARE
                v_user_id BIGINT := NEW.user_id;
                v_exists  BOOLEAN;
            BEGIN
                SELECT EXISTS (
                    SELECT 1 FROM zaposleni WHERE user_id = v_user_id
                ) INTO v_exists;

                IF v_exists THEN
                    RAISE EXCEPTION
                        'Disjointness violation: user % is already registered as Zaposlen and cannot also be Putnik.',
                        v_user_id
                        USING ERRCODE = '23P01';
                END IF;

                RETURN NEW;
            EXCEPTION
                WHEN OTHERS THEN
                    RAISE;
            END;
            \$\$;

            CREATE TRIGGER before_insert_putnici
                BEFORE INSERT ON putnici
                FOR EACH ROW
                EXECUTE FUNCTION guard_putnik_not_zaposlen();


            CREATE OR REPLACE FUNCTION guard_zaposlen_not_putnik()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS \$\$
            DECLARE
                v_user_id BIGINT := NEW.user_id;
                v_exists  BOOLEAN;
            BEGIN
                SELECT EXISTS (
                    SELECT 1 FROM putnici WHERE user_id = v_user_id
                ) INTO v_exists;

                IF v_exists THEN
                    RAISE EXCEPTION
                        'Disjointness violation: user % is already registered as Putnik and cannot also be Zaposlen.',
                        v_user_id
                        USING ERRCODE = '23P01';
                END IF;

                RETURN NEW;
            EXCEPTION
                WHEN OTHERS THEN
                    RAISE;
            END;
            \$\$;

            CREATE TRIGGER before_insert_zaposleni
                BEFORE INSERT ON zaposleni
                FOR EACH ROW
                EXECUTE FUNCTION guard_zaposlen_not_putnik();
        ");

        // ── Stored procedures for safe subtype creation ──────────────────────────
        // These wrap the INSERT in a BEGIN/EXCEPTION block and surface a friendly
        // message when the DB trigger fires, shielding callers from raw SQLSTATE.

        DB::unprepared("
            CREATE OR REPLACE PROCEDURE kreiraj_putnika(
                p_user_id           BIGINT,
                p_credit_card_number VARCHAR
            )
            LANGUAGE plpgsql
            AS \$\$
            BEGIN
                INSERT INTO putnici (user_id, credit_card_number, created_at, updated_at)
                VALUES (p_user_id, p_credit_card_number, NOW(), NOW());

            EXCEPTION
                WHEN SQLSTATE '23P01' THEN
                    RAISE EXCEPTION 'Ne može se kreirati Putnik: korisnik % je već Zaposlen.', p_user_id
                        USING ERRCODE = '23P01';
                WHEN unique_violation THEN
                    RAISE EXCEPTION 'Putnik za korisnika % već postoji.', p_user_id
                        USING ERRCODE = '23505';
            END;
            \$\$;


            CREATE OR REPLACE PROCEDURE kreiraj_zaposlenog(
                p_user_id            BIGINT,
                p_role               VARCHAR,
                p_datum_zaposlenja   DATE,
                p_status             VARCHAR
            )
            LANGUAGE plpgsql
            AS \$\$
            BEGIN
                INSERT INTO zaposleni (user_id, role, datum_zaposlenja, status, created_at, updated_at)
                VALUES (p_user_id, p_role, p_datum_zaposlenja, p_status, NOW(), NOW());

            EXCEPTION
                WHEN SQLSTATE '23P01' THEN
                    RAISE EXCEPTION 'Ne može se kreirati Zaposlen: korisnik % je već Putnik.', p_user_id
                        USING ERRCODE = '23P01';
                WHEN unique_violation THEN
                    RAISE EXCEPTION 'Zaposlen za korisnika % već postoji.', p_user_id
                        USING ERRCODE = '23505';
            END;
            \$\$;
        ");
    }

    public function down(): void
    {
        DB::unprepared("
            DROP TRIGGER IF EXISTS before_insert_putnici  ON putnici;
            DROP TRIGGER IF EXISTS before_insert_zaposleni ON zaposleni;
            DROP FUNCTION  IF EXISTS guard_putnik_not_zaposlen;
            DROP FUNCTION  IF EXISTS guard_zaposlen_not_putnik;
            DROP PROCEDURE IF EXISTS kreiraj_putnika;
            DROP PROCEDURE IF EXISTS kreiraj_zaposlenog;
        ");
    }
};
