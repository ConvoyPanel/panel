import { css } from '@emotion/react'
import styled from '@emotion/styled'
import { MultiSelect as MantineMultiSelect } from '@mantine/core'
import { ComponentProps, forwardRef } from 'react'
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
    ({ loading, nothingFound, error, ...props }, ref) => (
        <StyledMultiSelect
            error={error ? <ErrorMessage>{error}</ErrorMessage> : undefined}
            nothingFound={loading ? 'Loading...' : nothingFound}
            rightSection={loading && <LoadingDots size={4} />}
            ref={ref}
            {...props}
        />
    )
)

export default MultiSelect
