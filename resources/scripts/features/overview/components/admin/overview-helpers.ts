import byteSize from 'byte-size'

/** IEC byte formatting shared across the admin overview cards. */
export const bytes = (value: number) => {
    const { value: size, unit } = byteSize(value, {
        units: 'iec',
        precision: 1,
    })
    return `${size} ${unit}`
}

/** Thousands separators for counts. */
export const num = (value: number) => value.toLocaleString('en-US')

/**
 * Capacity meters read neutral until a resource runs hot, then amber, then
 * destructive. Semantic color is reserved for genuine pressure so a normal
 * dashboard stays calm.
 */
export type CapacityTone = 'normal' | 'warn' | 'crit'

export const capacityTone = (percent: number): CapacityTone =>
    percent >= 95 ? 'crit' : percent >= 80 ? 'warn' : 'normal'

/** Fill color for `LinearProgressBar`'s `indicatorClassName` by tone. */
export const meterIndicatorClass: Record<CapacityTone, string> = {
    normal: '',
    warn: 'bg-amber-500 dark:bg-amber-400',
    crit: 'bg-destructive',
}

/** Matching soft badge classes for the same tone. */
export const toneBadgeClass: Record<CapacityTone, string> = {
    normal: 'bg-muted text-muted-foreground',
    warn: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
    crit: 'bg-destructive/15 text-destructive',
}
