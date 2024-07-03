import { IconChevronRight } from '@tabler/icons-react'

import { Button } from '@/components/ui/Button'

interface Props {
    title: string
    description: string
    onClick: () => void
}

const AuthSetting = ({ title, description, onClick }: Props) => {
    return (
        <div className={'flex justify-between'}>
            <div className='space-y-0.5'>
                <p className='text-sm font-medium'>{title}</p>
                <p className='text-xs text-muted-foreground'>{description}</p>
            </div>
            <Button size={'icon'} variant={'ghost'} onClick={onClick}>
                <IconChevronRight className={'h-4 w-4 text-muted-foreground'} />
            </Button>
        </div>
    )
}

export default AuthSetting
