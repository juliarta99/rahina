import type { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/assets/images/logo_rahina.png"
            alt="Logo Rahina"
            {...props}
            className={`object-contain ${props.className ?? ''}`}
        />
    );
}

