import styled from '@emotion/styled'
import { CheckIcon } from '@heroicons/react/20/solid'
import {
    Select as MantineSelect,
    SelectProps as MantineSelectProps,
} from '@mantine/core'
import { ComponentPropsWithoutRef, forwardRef } from 'react'
import tw from 'twin.macro'

import ErrorMessage from '@/components/elements/ErrorMessage'
import LoadingDots from '@/components/elements/LoadingDots'

const StyledSelect = styled(MantineSelect)`
    & .mantine-Select-label {
        ${tw`text-xs font-medium text-accent-500 mb-1`}
    }

    & .mantine-Select-input {
        ${tw`bg-background`}
        ${({ error }) =>
            error
                ? tw`border-error placeholder:text-error-lighter text-error`
                : tw`border-accent-200 placeholder:text-accent-400 focus:border-accent-500`}
    }

    & .mantine-Select-dropdown {
        ${tw`bg-background shadow-lg dark:shadow-none border border-accent-200`}
    }

    & .mantine-Select-itemsWrapper {
        ${tw`p-2`}
    }
`

/* ${({ itemComponent }) =>
        !itemComponent
            ? css`
                  & .mantine-Select-item {
                      ${tw`px-2 h-12 sm:h-9 hover:bg-accent-200 text-accent-500 flex items-center`}
                  }

                  & .mantine-Select-item[data-selected] {
                      ${tw`font-medium text-foreground bg-background hover:bg-accent-200`}
                  }
              `
            : null} */

export interface SelectProps extends MantineSelectProps {
    loading?: boolean
}

interface SelectItemProps extends ComponentPropsWithoutRef<'div'> {
    label: string
}

const StyledSelectItem = styled.div`
    ${tw`text-sm p-2 hover:bg-accent-200 text-accent-500 flex items-center justify-between cursor-pointer rounded`}
    & .select-item-icon {
        ${tw`hidden`}
    }

    &[data-selected] {
        ${tw`font-medium text-foreground bg-background hover:bg-accent-200`}
    }

    &[data-selected] .select-item-icon {
        ${tw`block`}
    }
`

const SelectItem = forwardRef<HTMLDivElement, SelectItemProps>(
    ({ label, className, ...props }, ref) => (
        <StyledSelectItem ref={ref} {...props}>
            <span>{label}</span>
            <CheckIcon
                className='h-4 w-4 text-foreground select-item-icon'
                title='checked'
            />
        </StyledSelectItem>
    )
)

const Select = forwardRef<HTMLInputElement, SelectProps>(
    ({ loading, nothingFound, rightSection, error, ...props }, ref) => (
        <StyledSelect
            error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined}
            nothingFound={loading ? 'Loading...' : nothingFound}
            rightSection={loading ? <LoadingDots size={4} /> : rightSection}
            itemComponent={SelectItem}
            ref={ref}
            {...props}
        />
    )
)

export default Select
