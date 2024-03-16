import { forwardRef } from 'react'
import { useFormContext } from 'react-hook-form'

import { Button, ButtonProps } from '@/components/ui/Button'


const FormButton = forwardRef<
    HTMLButtonElement,
    Omit<ButtonProps, 'type' | 'loading'>
>((props, ref) => {
    const { formState } = useFormContext()

    return (
        <Button
            type={'submit'}
            loading={formState.isSubmitting}
            {...props}
            ref={ref}
        />
    )
})
FormButton.displayName = 'FormButton'

export default FormButton
