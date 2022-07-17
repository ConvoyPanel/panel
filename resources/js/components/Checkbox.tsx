import React from 'react'
import classNames from '@/util/classNames'

interface Props {
  name?: string
  id?: string
  value?: string
  checked?: boolean
  handleChange?: (event: React.ChangeEvent<HTMLInputElement>) => void
  danger?: boolean
}

export default function Checkbox({
  name,
  id,
  value,
  checked,
  handleChange,
  danger,
}: Props) {
  return (
    <input
      type='checkbox'
      name={name}
      id={id}
      value={value}
      checked={checked}
      className={classNames(
        danger
          ? 'text-red-600 focus:border-red-300 focus:ring-red-200'
          : 'text-blue-600 focus:border-blue-300 focus:ring-blue-200',
        'rounded border-gray-300 shadow-sm focus:ring focus:ring-opacity-50'
      )}
      onChange={(e) => handleChange && handleChange(e)}
    />
  )
}
