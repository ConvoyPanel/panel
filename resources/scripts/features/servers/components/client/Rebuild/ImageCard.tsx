import { ImageDefinition } from '@/types/image.ts'

import {
    Field,
    FieldContent,
    FieldDescription,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/Field'
import { RadioGroupItem } from '@/components/ui/RadioGroup'

interface Props {
    image: ImageDefinition
}

/** The version half of the picker; same radio card as ImageGroupCard. */
const ImageCard = ({ image }: Props) => {
    const id = `image-${image.uuid}`

    return (
        <FieldLabel htmlFor={id}>
            <Field orientation={'horizontal'}>
                <RadioGroupItem id={id} value={image.uuid} />
                <FieldContent>
                    <FieldTitle>{image.name}</FieldTitle>
                    {image.description && (
                        <FieldDescription>
                            {image.description}
                        </FieldDescription>
                    )}
                </FieldContent>
            </Field>
        </FieldLabel>
    )
}

export default ImageCard
