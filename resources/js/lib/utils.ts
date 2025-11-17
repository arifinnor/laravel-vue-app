import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

function getPathname(url: string): string {
    try {
        const urlObj = new URL(url, 'http://localhost');
        return urlObj.pathname;
    } catch {
        return url.split('?')[0].split('#')[0];
    }
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
): boolean {
    const checkUrl = toUrl(urlToCheck);
    if (!checkUrl) {
        return false;
    }

    const checkPathname = getPathname(checkUrl);
    const currentPathname = getPathname(currentUrl);

    if (checkPathname === currentPathname) {
        return true;
    }

    if (checkPathname !== '/' && currentPathname.startsWith(`${checkPathname}/`)) {
        return true;
    }

    return false;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
