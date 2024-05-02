import { Icon } from '@tabler/icons-react'
import { Link } from '@tanstack/react-router'

interface Props {
    heading?: string
    links: {
        label: string
        to?: string
        icon?: Icon
        onClick?: () => void
    }[]
}

const LargeSidebarSection = ({ heading, links }: Props) => {
    return (
        <div className={'border-default border-b py-5 px-6'}>
            {heading && (
                <h4 className={'mb-2 w-full text-sm text-muted-foreground'}>
                    {heading}
                </h4>
            )}
            <ul className={'space-y-1'}>
                {links.map(link => (
                    <Link
                        to={'/'}
                        className={
                            'border-default group flex max-w-full cursor-pointer items-center space-x-2 py-1 font-normal outline-none ring-foreground focus-visible:z-10 focus-visible:ring-1 group-hover:border-muted-foreground'
                        }
                    >
                        {link.icon && (
                            <span
                                className={
                                    'truncate text-sm text-muted-foreground transition group-hover:text-foreground'
                                }
                            >
                                <link.icon className={'h-5 w-5'} />
                            </span>
                        )}
                        <span
                            title={link.label}
                            className='w-full truncate text-sm text-muted-foreground transition group-hover:text-foreground'
                        >
                            {link.label}
                        </span>
                    </Link>
                ))}
            </ul>
        </div>
    )
}

export default LargeSidebarSection
