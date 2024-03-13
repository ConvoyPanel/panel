import { ReactNode } from 'react'

interface Props {
    title: string
    description?: string
    children?: ReactNode
}

interface FormSection extends React.FC<Props> {
    Divider: React.FC
}

const FormSection: FormSection = ({ title, description, children }: Props) => {
    return (
        <div>
            <div className='md:grid md:grid-cols-3 md:gap-6'>
                <div className='md:col-span-1'>
                    <h3 className='text-lg font-medium leading-6 text-foreground'>
                        {title}
                    </h3>
                    {description && (
                        <p className='mt-1 description-small'>{description}</p>
                    )}
                </div>
                <div className='flex flex-col space-y-8 mt-5 md:col-span-2 md:mt-0'>
                    {children}
                </div>
            </div>
        </div>
    )
}

FormSection.Divider = () => {
    return <div className='w-full my-5 h-[1px] bg-accent-200' />
}

export default FormSection
