import styled from '@emotion/styled'
import { ReactNode } from 'react'
import tw from 'twin.macro'

interface FormCard
    extends React.FC<{ children: ReactNode; className?: string }> {
    Title: React.FC<{ children: ReactNode }>
    Body: React.FC<{ children: ReactNode }>
    Footer: React.FC<{ children: ReactNode; className?: string }>
}

const FormCard: FormCard = ({ children, className }) => {
    return (
        <div
            className={`rounded border border-accent-200 bg-background dark:shadow-none ${className}`}
        >
            {children}
        </div>
    )
}

FormCard.Title = styled.h4`
    ${tw`text-foreground text-xl font-semibold`}
`

FormCard.Body = styled.div`
    ${tw`p-6 rounded-t bg-background`}
`
FormCard.Footer = styled.div`
    ${tw`px-6 py-3 rounded-b border-t border-accent-200 bg-accent-100 flex justify-center md:justify-end`}
`

export default FormCard
