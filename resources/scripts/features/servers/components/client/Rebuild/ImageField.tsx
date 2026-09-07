import ImageCard from '@/features/servers/components/client/Rebuild/ImageCard'
import { ImageGroup } from '@/types/image.ts'

import { Empty, EmptyDescription, EmptyHeader } from '@/components/ui/Empty'
import {
    FormControl,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/Form'
import { RadioGroup } from '@/components/ui/RadioGroup'

interface Props {
    group?: ImageGroup
}

const ImageField = ({ group }: Props) => {
    const definitions = group?.definitions ?? []

    if (!group || definitions.length === 0) {
        return (
            <Empty>
                <EmptyHeader>
                    <EmptyDescription>
                        {group
                            ? 'This operating system has no versions available.'
                            : 'Choose an operating system first.'}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        )
    }

    return (
        <FormField
            name={'imageUuid'}
            render={({ field }) => (
                <FormItem>
                    <FormControl>
                        <RadioGroup
                            value={field.value}
                            onValueChange={field.onChange}
                            className={
                                'grid-cols-[repeat(auto-fill,minmax(14rem,1fr))]'
                            }
                        >
                            {definitions.map(image => (
                                <ImageCard
                                    key={image.uuid}
                                    image={image}
                                />
                            ))}
                        </RadioGroup>
                    </FormControl>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default ImageField
