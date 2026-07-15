import { Button } from '@/components/ui/Button'
import {
    ResponsiveDialog,
    ResponsiveDialogBody,
    ResponsiveDialogClose,
    ResponsiveDialogContent,
    ResponsiveDialogFooter,
    ResponsiveDialogHeader,
    ResponsiveDialogTitle,
    ResponsiveDialogTrigger,
} from '@/components/ui/ResponsiveDialog'

const ConsoleButton = () => {
    return (
        <ResponsiveDialog>
            <ResponsiveDialogTrigger
                render={
                    <Button variant={'outline'}>Console</Button>
                }
            />
            <ResponsiveDialogContent>
                <ResponsiveDialogHeader>
                    <ResponsiveDialogTitle className={'text-sm font-normal'}>
                        Select a Console
                    </ResponsiveDialogTitle>
                </ResponsiveDialogHeader>
                <ResponsiveDialogBody>
                    <div className={'-mx-2 flex flex-col'}>
                        <button
                            className={
                                'py-2 px-2 text-left hover:bg-accent hover:text-accent-foreground'
                            }
                        >
                            <span className={'block text-lg font-bold'}>
                                NoVNC
                            </span>
                            <span className={'text-sm text-muted-foreground'}>
                                NoVNC offers the most compatibility across all
                                systems, making it an ideal choice for diverse
                                environments.
                            </span>
                        </button>
                        <button
                            className={
                                'py-2 px-2 text-left hover:bg-accent hover:text-accent-foreground'
                            }
                        >
                            <span className={'block text-lg font-bold'}>
                                XTerm.js
                            </span>
                            <span className={'text-sm text-muted-foreground'}>
                                XTerm.js provides faster performance and more
                                seamless native integration, though it may not
                                be compatible with certain systems (e.g.,
                                non-command line oriented systems).
                            </span>
                        </button>
                    </div>
                </ResponsiveDialogBody>
                <ResponsiveDialogFooter>
                    <ResponsiveDialogClose
                        render={
                            <Button variant={'outline'}>Close</Button>
                        }
                    />
                </ResponsiveDialogFooter>
            </ResponsiveDialogContent>
        </ResponsiveDialog>
    )
}

export default ConsoleButton
