import React from 'react';

interface Props {
    type?: React.ButtonHTMLAttributes<HTMLButtonElement>['type']
    className?: string
    processing?: boolean
    onClick?: () => void
    children?: React.ReactNode
}

export default function Button({ type = 'submit', className = '', onClick, processing, children }: Props) {
    return (
        <button
            type={type}
            className={
                `inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest active:bg-gray-900 transition ease-in-out duration-150 ${
                    processing && 'opacity-25'
                } ` + className
            }
            onClick={onClick}
            disabled={processing}
        >
            {children}
        </button>
    );
}
