import ImageIconDisplay from '@/features/images/components/ImageIconDisplay.tsx'
import { ImageIcon } from '@/types/image.ts'
import { useFormContext } from 'react-hook-form'

import { Button } from '@/components/ui/Button'
import SelectForm, {
    SelectFormItem,
} from '@/components/ui/Forms/SelectForm.tsx'

export const formatIconLabel = (icon: string) => {
    return icon
        .split('_')
        .map(s => s.charAt(0).toUpperCase() + s.slice(1))
        .join(' ')
}

const ImageIconSelect = () => {
    const { setValue } = useFormContext()

    return (
        <div>
            <SelectForm
                name={'icon'}
                label={'Icon'}
                placeholder={'Select an icon...'}
                items={Object.values(ImageIcon).map(
                    (icon): SelectFormItem => ({
                        value: icon,
                        label: (
                            <div className={'flex items-center gap-2'}>
                                <ImageIconDisplay
                                    icon={icon}
                                    className={'size-5'}
                                />
                                <span>{formatIconLabel(icon)}</span>
                            </div>
                        ),
                    })
                )}
            />
            <Button
                variant={'link'}
                className={'ml-auto block px-0'}
                onClick={() => setValue('icon', null, { shouldDirty: true })}
                type='button'
            >
                Clear
            </Button>
        </div>
    )
}

export default ImageIconSelect
