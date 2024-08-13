import { Button } from '@/components/ui/Button'
import {
    Credenza,
    CredenzaBody,
    CredenzaClose,
    CredenzaContent,
    CredenzaFooter,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaTrigger,
} from '@/components/ui/Credenza'

const ConsoleButton = () => {
    return (
        <Credenza>
            <CredenzaTrigger asChild>
                <Button variant={'outline'}>Console</Button>
            </CredenzaTrigger>
            <CredenzaContent>
                <CredenzaHeader>
                    <CredenzaTitle className={'text-sm font-normal'}>
                        Select a Console
                    </CredenzaTitle>
                </CredenzaHeader>
                <CredenzaBody>
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
                </CredenzaBody>
                <CredenzaFooter>
                    <CredenzaClose asChild>
                        <Button variant={'outline'}>Close</Button>
                    </CredenzaClose>
                </CredenzaFooter>
            </CredenzaContent>
        </Credenza>
    )
}

export default ConsoleButton
