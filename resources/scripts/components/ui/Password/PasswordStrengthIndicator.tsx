import { cn } from '@/utils'
import { evaluatePassword } from '@/utils/password.ts'
import { IconCheck, IconX } from '@tabler/icons-react'
import { useMemo } from 'react'

interface Props {
    password: string
}

const Indicator = ({
    criterion,
    isFulfilled,
}: {
    criterion: string
    isFulfilled: boolean
}) => {
    const Icon = isFulfilled ? IconCheck : IconX

    return (
        <div className={'flex items-center'}>
            <Icon
                className={cn(
                    'mr-2 h-4 w-4 shrink-0',
                    isFulfilled ? 'text-primary' : 'text-destructive'
                )}
            />
            <span>{criterion}</span>
        </div>
    )
}

const PasswordStrengthIndicator = ({ password }: Props) => {
    const criteria = useMemo(() => evaluatePassword(password), [password])

    return (
        <div className={'bg-accent-muted space-y-2 p-2'}>
            <ul>
                {criteria.map(({ label, isFulfilled }) => (
                    <li key={label}>
                        <Indicator
                            criterion={label}
                            isFulfilled={isFulfilled}
                        />
                    </li>
                ))}
            </ul>
            <p className={'text-muted-foreground text-sm'}>
                Length is what matters. Several unrelated words you can remember
                make a stronger password than a short one with substitutions.
            </p>
        </div>
    )
}

export default PasswordStrengthIndicator
