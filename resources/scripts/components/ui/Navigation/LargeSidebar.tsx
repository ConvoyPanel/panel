import { Link } from '@tanstack/react-router'

interface Props {
    heading?: string
}

const LargeSidebar = ({ heading }: Props) => {
    // TODO: change bg-studio
    return (
        <div
            className={
                'bg-studio hide-scrollbar border-default h-full w-64 overflow-auto border-r'
            }
        >
            <div
                className={
                    'border-default flex h-12 max-h-12 items-center border-b px-6'
                }
            >
                <h3 className={'truncate text-lg'}>{heading}</h3>
            </div>
            <nav
                role='menu'
                aria-label='Sidebar'
                aria-orientation='vertical'
                aria-labelledby='options-menu'
            >
                <div className={'border-default border-b py-5 px-6'}>
                    <h4 className={'mb-2 w-full text-sm text-muted-foreground'}>
                        Servers
                    </h4>
                    <ul className={'space-y-1'}>
                        <Link
                            to={'/'}
                            className={
                                'border-default group flex max-w-full cursor-pointer items-center space-x-2 py-1 font-normal outline-none ring-foreground focus-visible:z-10 focus-visible:ring-1 group-hover:border-muted-foreground'
                            }
                        >
                            <span
                                title='All projects'
                                className='w-full truncate text-sm text-muted-foreground transition group-hover:text-foreground'
                            >
                                All servers
                            </span>
                        </Link>
                    </ul>
                </div>
            </nav>
        </div>
    )
}

export default LargeSidebar
