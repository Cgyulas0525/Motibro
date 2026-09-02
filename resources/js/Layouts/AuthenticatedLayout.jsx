import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const menuItems = [
    {
        label: 'Indító pult',
        href: '/dashboard',
        routeName: 'dashboard',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        ),
    },
    {
        label: 'Időpontok',
        href: '/booking-rules',
        routeName: 'booking-rules.*',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        ),
    },
    {
        label: 'Ütemezés',
        href: '/settings/scheduler',
        routeName: 'settings.scheduler.*',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        ),
    },
    {
        label: 'Futások',
        href: '/booking-runs',
        routeName: 'booking-runs.*',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        ),
    },
    {
        label: 'Foglalt slotok',
        href: '/booking-slot-bookings',
        routeName: 'booking-slot-bookings.*',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        ),
    },
    {
        label: 'Kísérletek',
        href: '/booking-attempts',
        routeName: 'booking-attempts.*',
        icon: (
            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        ),
    },
];

function NavItem({ item, sidebarOpen, onClose }) {
    const isActive = item.routeName ? route().current(item.routeName) : false;

    return (
        <Link
            href={item.href}
            onClick={onClose}
            className={`flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-colors duration-150 ${
                isActive
                    ? 'bg-brand-600 text-white'
                    : 'text-brand-100 hover:bg-brand-700 hover:text-white'
            }`}
            title={!sidebarOpen ? item.label : undefined}
        >
            <span className="flex-shrink-0">{item.icon}</span>
            {sidebarOpen && <span>{item.label}</span>}
        </Link>
    );
}

export default function AuthenticatedLayout({ header, children, mainClassName }) {
    const user = usePage().props.auth.user;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth < 768) setSidebarOpen(false);
        };
        setSidebarOpen(window.innerWidth >= 768);
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    const closeSidebarOnMobile = () => {
        if (window.innerWidth < 768) setSidebarOpen(false);
    };

    return (
        <div className="flex h-screen bg-cream">
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 md:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            <aside className={[
                'fixed inset-y-0 left-0 z-50 flex flex-col bg-brand-800 text-white transition-all duration-200',
                'md:static md:inset-auto md:z-auto',
                sidebarOpen
                    ? 'w-64 md:w-56 translate-x-0'
                    : 'w-64 md:w-16 -translate-x-full md:translate-x-0',
            ].join(' ')}>
                <Link
                    href="/dashboard"
                    onClick={closeSidebarOnMobile}
                    className="flex items-center gap-3 px-4 py-4 border-b border-brand-700 hover:bg-brand-700 transition-colors"
                >
                    <ApplicationLogo className="h-8 w-8 shrink-0" />
                    {sidebarOpen && (
                        <span className="text-lg font-bold tracking-wide text-white">Motibro</span>
                    )}
                </Link>

                <nav className="flex-1 py-2 overflow-y-auto notranslate" translate="no">
                    {menuItems.map((item) => (
                        <NavItem
                            key={item.label}
                            item={item}
                            sidebarOpen={sidebarOpen}
                            onClose={closeSidebarOnMobile}
                        />
                    ))}
                </nav>
            </aside>

            <div className="flex flex-col flex-1 min-w-0 overflow-hidden">
                <header className="flex items-center justify-between h-14 bg-brand-800 border-b border-brand-700 px-4 flex-shrink-0">
                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setSidebarOpen((v) => !v)}
                            className="text-brand-100 hover:text-white focus:outline-none"
                        >
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        {header && (
                            <div className="text-sm text-white font-semibold notranslate" translate="no">{header}</div>
                        )}
                    </div>

                    <Dropdown>
                        <Dropdown.Trigger>
                            <button className="flex items-center gap-2 text-sm text-brand-100 hover:text-white focus:outline-none">
                                <div className="h-8 w-8 rounded-full bg-brand-600 flex items-center justify-center text-white font-semibold text-xs">
                                    {(user?.name ?? '?').charAt(0).toUpperCase()}
                                </div>
                                <span className="hidden sm:block font-medium">{user?.name ?? ''}</span>
                                <svg className="h-4 w-4 text-brand-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content>
                            <Dropdown.Link href={route('profile.edit')}>Profil</Dropdown.Link>
                            <Dropdown.Link href={route('logout')} method="post" as="button">
                                Kijelentkezés
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </header>

                <main className={`flex-1 overflow-y-auto p-4 md:p-6 ${mainClassName ?? ''}`}>
                    {children}
                </main>
            </div>
        </div>
    );
}
