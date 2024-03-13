import styled from '@emotion/styled'
import { CheckIcon } from '@heroicons/react/20/solid'
import { ComponentPropsWithoutRef, forwardRef } from 'react'
import tw from 'twin.macro'

interface DescriptiveItemComponentProps
    extends ComponentPropsWithoutRef<'div'> {
    label: string
    description: string
}

const StyledDescriptiveItemComponentProps = styled.div`
    ${tw`flex items-center justify-between p-2 hover:bg-accent-200 cursor-pointer`}

    & .select-item-label {
        ${tw`text-sm font-medium text-foreground text-accent-500`}
    }

    &[data-selected] .select-item-label {
        ${tw`font-medium text-foreground`}
    }

    & .select-item-description {
        ${tw`text-xs text-accent-400`}
    }

    & .select-item-icon {
        ${tw`hidden`}
    }

    &[data-selected] .select-item-icon {
        ${tw`block`}
    }
`

const DescriptiveItemComponent = forwardRef<
    HTMLDivElement,
    DescriptiveItemComponentProps
>(({ label, description, className, ...props }, ref) => (
    <StyledDescriptiveItemComponentProps ref={ref} {...props}>
        <div>
            <p className='select-item-label'>{label}</p>
            <p className='select-item-description'>{description}</p>
        </div>
        <CheckIcon
            className='h-4 w-4 text-foreground select-item-icon'
            title='checked'
        />
    </StyledDescriptiveItemComponentProps>
))

export default DescriptiveItemComponent
