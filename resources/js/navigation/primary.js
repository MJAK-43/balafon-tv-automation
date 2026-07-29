import {
    CalendarDaysIcon,
    CircleStackIcon,
    CommandLineIcon,
    HomeIcon,
    PhotoIcon,
    QueueListIcon,
    SignalIcon,
    TvIcon,
} from '@heroicons/vue/24/outline';

export const primaryNavigation = [
    { name: 'Vue générale', shortName: 'Accueil', to: { name: 'dashboard' }, icon: HomeIcon, index: '01' },
    { name: 'Diffusion', shortName: 'Direct', to: { name: 'diffusion' }, icon: SignalIcon, index: '02' },
    { name: 'Médias', shortName: 'Médias', to: { name: 'media' }, icon: CircleStackIcon, index: '03' },
    { name: 'Habillages', shortName: 'Habillage', to: { name: 'branding' }, icon: PhotoIcon, index: '04' },
    { name: 'Playlists', shortName: 'Playlists', to: { name: 'playlists' }, icon: QueueListIcon, index: '05' },
    { name: 'Planification', shortName: 'Planning', to: { name: 'scheduling' }, icon: CalendarDaysIcon, index: '06' },
    { name: 'Canaux', shortName: 'Canaux', to: { name: 'channels' }, icon: TvIcon, index: '07' },
];

export const utilityNavigation = [
    { name: 'Diagnostic système', to: { name: 'system-diagnostic' }, icon: CommandLineIcon },
];
