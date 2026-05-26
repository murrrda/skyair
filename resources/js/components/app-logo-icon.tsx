import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: Omit<ImgHTMLAttributes<HTMLImageElement>, 'src' | 'alt'>) {
    return <img src="/skyair-logo.png" alt="SkyAir" {...props} />;
}