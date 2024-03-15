import { ComponentProps } from 'react'

interface Props extends ComponentProps<'svg'> {}

const Logo = (props: Props) => {
    return (
        <svg
            xmlns='http://www.w3.org/2000/svg'
            viewBox='0 0 512 512'
            {...props}
        >
            <path
                fill={'currentColor'}
                d='M381.87,21.81c-17.65,17.24-24.99,40.87-22.01,63.43l-105.77-22.38h0c-13.01-2.59-27.05,1.05-37.2,10.96-16.19,15.82-16.33,41.6-.31,57.58,5.75,5.74,12.81,9.4,20.23,11.05v.03s.98,.21,.98,.21c0,0,.01,0,.02,0l64.88,13.73L104.35,350.22c-24.11-41.1-28.27-90.44-12.56-134.37h-.02c7.92-21.43-3-45.05-24.39-52.76-21.39-7.71-45.16,3.4-53.08,24.82-30.75,85.04-11.93,183.71,56.69,252.18,68.9,68.76,169.15,88.25,255.84,58.27v-.03c21.81-7.47,33.46-30.75,26-51.99-7.46-21.25-31.19-32.42-53.02-24.95h0c-45.04,15.68-95.62,11.23-137.46-13.28l198.17-193.61,13.33,64.75,.23-.05c1.55,7.46,5.22,14.59,11.05,20.41,16.02,15.99,42.13,16.12,58.32,.31,11.29-11.03,14.75-26.9,10.39-40.91l-22.28-108.24c20.74,1.08,41.85-6.15,57.78-21.71,29.99-29.3,30.25-77.06,.57-106.68-29.68-29.61-78.05-29.87-108.04-.57Zm80.98,80.82c-15.21,14.86-39.74,14.73-54.79-.29-15.05-15.02-14.92-39.24,.29-54.11,15.21-14.86,39.74-14.73,54.79,.29,15.05,15.02,14.92,39.24-.29,54.11Z'
            />
        </svg>
    )
}

export default Logo
