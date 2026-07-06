import { useFormContext } from 'react-hook-form';
import { TemplateIcon } from '@/types/template-group.ts';
import SelectForm, { SelectFormItem } from '@/components/ui/Forms/SelectForm.tsx';
import TemplateIconDisplay from '@/features/template-groups/components/TemplateIconDisplay.tsx';
import { Button } from '@/components/ui/Button';

export const formatIconLabel = (icon: string) => {
    return icon
        .split('_')
        .map(s => s.charAt(0).toUpperCase() + s.slice(1))
        .join(' ');
};

const TemplateIconSelect = () => {
    const { setValue } = useFormContext();

    return (
        <div>
            <SelectForm
                name={'icon'}
                label={'Icon'}
                placeholder={'Select an icon...'}
                items={Object.values(TemplateIcon).map(
                    (icon): SelectFormItem => ({
                        value: icon,
                        label: (
                            <div className={'flex items-center gap-2'}>
                                <TemplateIconDisplay
                                    icon={icon}
                                    className={'size-5'}
                                />
                                <span>{formatIconLabel(icon)}</span>
                            </div>
                        ),
                    }),
                )}
            />
            <Button
                variant={'link'}
                className={'ml-auto block px-0'}
                onClick={() => setValue('icon', null, { shouldDirty: true })}
                type="button"
            >
                Clear
            </Button>
        </div>
    );
};

export default TemplateIconSelect;
