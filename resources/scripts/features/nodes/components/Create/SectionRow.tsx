import { ReactNode } from 'react'

interface Props {
    title: string
    description?: ReactNode
    children: ReactNode
}

/**
 * A settings-style section: a title + blurb in a left column and the fields in
 * a right column (stacks on narrow widths). Parent stacks these with a
 * `divide-y` so the description never interrupts the field flow.
 */
const SectionRow = ({ title, description, children }: Props) => {
    return (
        <div
            className={
                'grid gap-4 py-7 md:grid-cols-[260px_minmax(0,1fr)] md:gap-10'
            }
        >
            <div>
                <h3 className={'text-base font-semibold text-foreground'}>
                    {title}
                </h3>
                {/* The ch cap only bites below md, where the grid collapses to
                    one column and the blurb would otherwise run the full page
                    width. At md+ the 260px column governs. */}
                {description && (
                    <p
                        className={
                            'mt-1 max-w-[42ch] text-sm leading-relaxed text-muted-foreground'
                        }
                    >
                        {description}
                    </p>
                )}
            </div>
            <div className={'flex flex-col gap-4'}>{children}</div>
        </div>
    )
}

export default SectionRow
