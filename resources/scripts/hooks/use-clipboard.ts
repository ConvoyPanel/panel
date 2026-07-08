import { useCallback, useEffect, useRef, useState } from 'react'
import { toast } from 'sonner'

interface UseClipboardOptions {
    /** Time in ms after which the copied state will reset, `2000` by default */
    timeout?: number
    /** If set, a success toast with this message is shown on a successful copy. */
    successMessage?: string
}

interface UseClipboardReturnValue {
    /** Function to copy value to clipboard */
    copy: (value: any) => void

    /** Function to reset copied state and error */
    reset: () => void

    /** Boolean indicating if the value was copied successfully */
    copied: boolean
}

const useClipboard = (
    options?: UseClipboardOptions
): UseClipboardReturnValue => {
    const [copied, setCopied] = useState(false)
    const timeoutRef = useRef<number | null>(null)

    const copy = useCallback(
        (value: any) => {
            if (timeoutRef.current) {
                window.clearTimeout(timeoutRef.current)
            }

            navigator.clipboard
                .writeText(value)
                .then(() => {
                    if (options?.successMessage) {
                        toast.success(options.successMessage)
                    }
                    setCopied(true)
                    timeoutRef.current = window.setTimeout(() => {
                        setCopied(false)
                    }, options?.timeout ?? 2000)
                })
                .catch(err => {
                    toast.error('Failed to copy to clipboard')
                    throw err
                })
        },
        [options?.timeout, options?.successMessage]
    )

    const reset = useCallback(() => {
        setCopied(false)
        if (timeoutRef.current) {
            window.clearTimeout(timeoutRef.current)
        }
    }, [])

    useEffect(() => {
        return () => {
            if (timeoutRef.current) {
                window.clearTimeout(timeoutRef.current)
            }
        }
    }, [])

    return { copy, reset, copied }
}

export default useClipboard
