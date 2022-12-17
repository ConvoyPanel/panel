import styled from '@emotion/styled'
import { Select as MantineSelect } from '@mantine/core'
import tw from 'twin.macro'
import { css } from '@emotion/react'
import LoadingDots from '@/components/elements/LoadingDots'
import { ComponentProps } from 'react'
import ErrorMessage from '@/components/elements/ErrorMessage'

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

    ${({ itemComponent }) =>
        !itemComponent
            ? css`
                  & .mantine-Select-item {
                      ${tw`px-2 h-12 sm:h-9 hover:bg-accent-200 text-accent-500`}
                  }

                  & .mantine-Select-item[data-selected] {
                      ${tw`font-medium text-foreground bg-background hover:bg-accent-200`}
                  }
              `
            : null}
`

export interface SelectProps extends ComponentProps<typeof StyledSelect> {
    loading?: boolean
}

const Select = ({ loading, error, ...props }: SelectProps) => (
    <StyledSelect
        error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined}
        nothingFound={loading && 'Loading...'}
        rightSection={loading && <LoadingDots size={4} />}
        {...props}
    />
)

export default Select
