import { Template } from '@/types/template.ts'
import { IconDots } from '@tabler/icons-react'

import { badgeVariants } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/DropdownMenu'

interface Props {
    template: Template
}

const TemplateCard = ({ template }: Props) => {
    return (
        <div className={'flex items-center py-2 min-h-[3.75rem]'}>
            <div className={'flex grow flex-col justify-center'}>
                <h3
                    className={'flex items-center gap-2 truncate font-semibold'}
                >
                    {template.name}{' '}
                    <span
                        className={badgeVariants({
                            variant: 'secondary',
                        })}
                    >
                        {template.vmid}
                    </span>
                </h3>
                {template.description && (
                    <p className={'text-sm text-muted-foreground'}>
                        {template.description}
                    </p>
                )}
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button size={'icon'} variant={'ghost'}>
                        <IconDots className={'size-4 text-muted-foreground'} />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align='end' className={'w-60'}>
                    <DropdownMenuItem>Edit</DropdownMenuItem>
                    <DropdownMenuItem>Delete</DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    )
}

export default TemplateCard
