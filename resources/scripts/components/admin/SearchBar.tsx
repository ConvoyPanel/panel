import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/react/20/solid'
import { ChangeEventHandler, MouseEventHandler } from 'react'
import { useTranslation } from 'react-i18next'

import Button from '@/components/elements/Button'
import TextInput from '@/components/elements/inputs/TextInput'

interface Props {
    value?: string
    onChange?: ChangeEventHandler<HTMLInputElement>
    buttonText: string
    onClick: MouseEventHandler<HTMLButtonElement>
}

const SearchBar = ({ value, onChange, buttonText, onClick }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    return (
        <div className='flex space-x-2 items-center mb-3'>
            <TextInput
                icon={
                    <MagnifyingGlassIcon className='text-accent-400 w-4 h-4' />
                }
                className='grow'
                value={value}
                onChange={onChange}
                placeholder={`${tStrings('search')}...`}
            />
            <Button
                className='grid sm:hidden place-items-center'
                onClick={onClick}
                shape='square'
                variant='filled'
            >
                <PlusIcon className='w-5 h-5 block sm:hidden' />
            </Button>
            <Button
                className='hidden sm:block'
                onClick={onClick}
                variant='filled'
            >
                {buttonText}
            </Button>
        </div>
    )
}

export default SearchBar