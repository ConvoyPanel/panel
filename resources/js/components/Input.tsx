import classNames from '@/util/classNames'
import React, { KeyboardEvent, KeyboardEventHandler, useEffect, useRef } from 'react'

interface Props {
  type: string
  name: string
  defaultValue?: string,
  value?: string
  className?: string
  isFocused?: boolean
  placeholder?: string
  handleChange?: (event: React.ChangeEvent<HTMLInputElement>) => void
  onEnter?: (event: KeyboardEvent<HTMLInputElement>) => void
  autoComplete?: string
  required?: boolean
  prefix?: string
  block?: boolean
}

export default function Input({
  type = 'text',
  name,
  defaultValue,
  value,
  className,
  autoComplete,
  required,
  isFocused,
  placeholder,
  handleChange,
  onEnter,
  prefix,
  block,
}: Props) {
  const input = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (isFocused) {
      input.current?.focus()
    }
  }, [])

  const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === 'Enter') {
      onEnter?.(event)
    }
  }

  return (
    <div className={'flex relative ' + className}>
      {prefix && (
        <span className='inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500'>
          {prefix}
        </span>
      )}
      <input
        type={type}
        name={name}
        defaultValue={defaultValue}
        value={value}
        onKeyDown={(e) => handleKeyDown(e)}
        placeholder={placeholder}
        className={classNames(
          prefix ? 'rounded-none rounded-r-md' : 'rounded-md', block ? 'w-full' : '',
          `border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 inset-x-0 shadow-sm`
        )}
        ref={input}
        autoComplete={autoComplete}
        required={required}
        onChange={(e) => handleChange && handleChange(e)}
      />
    </div>
  )
}
