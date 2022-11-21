import { useRef, useState } from 'react'
import { useOutsideClick } from '@/util/useOutsideClick'
import Button from '@/components/elements/Button'
import { FocusableMode, isFocusableElement } from '@/util/focusManagement'

const Menu = () => {
  const [open, setOpen] = useState(false)
  const buttonRef = useRef<HTMLButtonElement>(null)
  const itemsRef = useRef<HTMLDivElement>(null)

  useOutsideClick(
    [buttonRef, itemsRef],
    (event, target) => {
      setOpen(false)

      if (!isFocusableElement(target, FocusableMode.Loose)) {
        event.preventDefault()
        buttonRef.current?.focus()
      }
    },
    open
  )

  return (
    <div className='relative'>
      <Button onClick={() => setOpen(!open)} ref={buttonRef}>
        Test
      </Button>
      {open && (
        <div
          ref={itemsRef}
          className='absolute mt-1 w-full p-2 overflow-auto rounded bg-background shadow-lg dark:shadow-none border border-accent-200 focus:outline-none sm:text-sm'
        ></div>
      )}
    </div>
  )
}

export default Menu
