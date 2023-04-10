import styled from '@emotion/styled'
import { Checkbox as MantineCheckbox, CheckboxProps } from '@mantine/core'
import { CheckIcon, MinusIcon } from '@heroicons/react/20/solid'
import { classNames } from '@/util/helpers'
import tw from 'twin.macro'
import { forwardRef } from 'react'

const StyledCheckbox = styled(MantineCheckbox)`
    .mantine-Checkbox-body {
        ${tw`items-center`}
    }
    .mantine-Checkbox-inner {
        ${tw`w-4 h-4`}
    }

    .mantine-Checkbox-input {
        ${tw`w-4 h-4 rounded-[3px] border-accent-500 hover:border-foreground active:bg-accent-200`}
    }
    .mantine-Checkbox-input:checked {
        ${tw`border-foreground`}
        ${({ indeterminate }) => (!indeterminate ? tw`bg-foreground` : null)}]
    }
`

const Icon = ({ indeterminate, className }: { indeterminate: boolean; className: string }) => {
    return !indeterminate ? (
        <CheckIcon className={classNames('stroke-2 stroke-background text-background', className)} />
    ) : (
        <MinusIcon
            className={classNames(
                'stroke-2',
                false ? 'stroke-accent-300 text-accent-300' : 'stroke-foreground text-foreground',
                className
            )}
        />
    )
}

const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>((props, ref) => {
    return <StyledCheckbox ref={ref} icon={Icon} {...props} />
})

export default Checkbox
