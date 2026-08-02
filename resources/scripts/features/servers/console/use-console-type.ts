import { useCallback, useState } from 'react'

import type { ConsoleType } from './api'

const storageKey = 'convoy:console-type'

const isConsoleType = (value: unknown): value is ConsoleType =>
    value === 'novnc' || value === 'xtermjs'

const read = () => {
    const stored = localStorage.getItem(storageKey)

    return isConsoleType(stored) ? stored : null
}

/**
 * The console type this browser last opened, or null when there is nothing to
 * go on yet. The split button opens straight into a remembered type; without
 * one it falls back to the picker rather than guessing, so the first visit is
 * the only one that has to read the difference between the two.
 */
const useConsoleType = () => {
    const [type, setType] = useState<ConsoleType | null>(read)

    const remember = useCallback((value: ConsoleType) => {
        setType(value)
        localStorage.setItem(storageKey, value)
    }, [])

    return [type, remember] as const
}

export default useConsoleType
