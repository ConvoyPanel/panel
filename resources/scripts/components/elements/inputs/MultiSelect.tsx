import { css } from '@emotion/react'
import styled from '@emotion/styled'
import { MultiSelect as MantineMultiSelect } from '@mantine/core'
import { ComponentProps, forwardRef, useRef } from 'react'
import tw from 'twin.macro'

import ErrorMessage from '@/components/elements/ErrorMessage'
import LoadingDots from '@/components/elements/LoadingDots'

const StyledMultiSelect = styled(MantineMultiSelect)`
    & .mantine-MultiSelect-label {
        ${tw`text-xs font-medium text-accent-500 mb-1`}
    }

    & .mantine-MultiSelect-input {
        ${tw`bg-background`}
        ${({ error }) =>
            error
                ? tw`border-error placeholder:text-error-lighter text-error`
                : tw`border-accent-200 placeholder:text-accent-400 focus:border-accent-500`}
    }

    & .mantine-MultiSelect-dropdown {
        ${tw`bg-background shadow-lg dark:shadow-none border border-accent-200`}
    }

    & .mantine-MultiSelect-itemsWrapper {
        ${tw`p-2`}
    }

    ${({ itemComponent }) =>
        !itemComponent
            ? css`
                  & .mantine-MultiSelect-item {
                      ${tw`px-2 h-12 sm:h-9 hover:bg-accent-200 text-accent-500 flex items-center`}
                  }
              `
            : null}
`

export interface MultiSelectProps
    extends ComponentProps<typeof StyledMultiSelect> {
    loading?: boolean
}

const MultiSelect = forwardRef<HTMLInputElement, MultiSelectProps>(
    (
        {
            loading,
            nothingFound,
            error,
            onKeyDown,
            onDropdownOpen,
            onDropdownClose,
            ...props
        },
        ref
    ) => {
        const opened = useRef(false)

        return (
            <StyledMultiSelect
                error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined}
                nothingFound={loading ? 'Loading...' : nothingFound}
                rightSection={loading && <LoadingDots size={4} />}
                ref={ref}
                onDropdownOpen={() => {
                    opened.current = true
                    onDropdownOpen?.()
                }}
                onDropdownClose={() => {
                    opened.current = false
                    onDropdownClose?.()
                }}
                onKeyDown={event => {
                    // Escape with the list open only closes the list. Mantine's
                    // Select marks that Escape as handled and MultiSelect
                    // doesn't, so the modal around it took the key as its own
                    // and closed, losing the form.
                    if (event.key === 'Escape' && opened.current) {
                        event.preventDefault()
                    }
                    onKeyDown?.(event)
                }}
                {...props}
            />
        )
    }
)

export default MultiSelect
