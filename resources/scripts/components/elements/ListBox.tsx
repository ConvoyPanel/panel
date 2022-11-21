import Drawer from '@/components/elements/Drawer'
import useWindowDimensions from '@/util/useWindowDimensions'
import { CheckIcon, ChevronDownIcon } from '@heroicons/react/20/solid'
import { Fragment, useEffect, useMemo, useRef, useState } from 'react'
import TextInput, { Size } from '@/components/elements/inputs/TextInput'
import { Transition } from '@headlessui/react'

export interface Datum {
  label: string
  value: string
  disabled?: boolean
}

interface Props {
  label?: string
  size?: Size
  data: Datum[]
  selected: string
  onSelect: (payload: string) => void
}

const ListBox = ({ label, size, data, selected, onSelect }: Props) => {
  const active = useMemo(
    () => data.find((datum) => datum.value === selected),
    [data, selected]
  )
  const { width } = useWindowDimensions()
  const [open, setOpen] = useState(false)
  const [useDrawer, setUseDrawer] = useState(width < 640)
  const ListBoxRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      //@ts-expect-error
      if (ListBoxRef.current && !ListBoxRef.current.contains(event.target)) {
        setOpen(false)
      }
    }
    document.addEventListener('click', handleClickOutside, true)
    return () => {
      document.removeEventListener('click', handleClickOutside, true)
    }
  }, [])

  useEffect(() => {
    setUseDrawer(width < 640)
  }, [width])

  const handleSelect = (value: string) => {
    onSelect(value)
    setOpen(false)
  }

  return (
    <div className='relative' ref={ListBoxRef}>
    { label && <label className='text-xs font-medium text-accents-500'>{label}</label>}
      <TextInput
        className={`${open && 'border-accent-500'}`}
        onClick={() => setOpen(!open)}
        value={active?.label}
        size={size}
        suffix={
          <ChevronDownIcon
            className={`ml-4 h-3 w-3 text-accent-300 hover:text-foreground transition ${
              open && 'rotate-180'
            }`}
          />
        }
        readOnly
      />

      {!useDrawer ? (
        <Transition
          show={open}
          as={Fragment}
          leave='transition ease-in duration-100'
          leaveFrom='opacity-100'
          leaveTo='opacity-0'
        >
          <div className='absolute mt-1 w-full p-2 overflow-auto rounded bg-white shadow-lg dark:shadow-none border border-accent-200 focus:outline-none sm:text-sm'>
            {data.map((datum) => (
              <div
                key={datum.value}
                onClick={() => handleSelect(datum.value)}
                className={`flex justify-between items-center relative select-none px-2 h-12 sm:h-9 hover:bg-accent-200 cursor-pointer rounded ${
                  datum.value === selected
                    ? 'font-medium text-foreground'
                    : 'text-accent-500'
                }`}
              >
                <span className='block truncate'>{datum.label}</span>
                {datum.value === selected && (
                  <div className='inset-y-0 left-0 flex items-center pl-3 text-foreground'>
                    <CheckIcon className='h-4 w-4' aria-hidden='true' />
                  </div>
                )}
              </div>
            ))}
          </div>
        </Transition>
      ) : (
        <Drawer open={open} onClose={() => setOpen(false)}>
          <div className='p-2'>
            {data.map((datum) => (
              <div
                key={datum.value}
                onClick={() => handleSelect(datum.value)}
                className={`flex justify-between items-center relative select-none px-2 h-12 sm:h-9 hover:bg-accent-200 cursor-pointer rounded ${
                  datum.value === selected
                    ? 'font-medium text-foreground'
                    : 'text-accent-500'
                }`}
              >
                <span className='block truncate'>{datum.label}</span>
                {datum.value === selected && (
                  <div className='inset-y-0 left-0 flex items-center pl-3 text-foreground'>
                    <CheckIcon className='h-4 w-4' aria-hidden='true' />
                  </div>
                )}
              </div>
            ))}
          </div>
        </Drawer>
      )}
    </div>
  )
}

export default ListBox
