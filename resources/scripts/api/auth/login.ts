import axios from '@/lib/axios.ts'

interface LoginParams {
    email: string
    password: string
}

const login = async ({ email, password }: LoginParams) => {
    await axios.post('/login', {
        email,
        password,
    })
}

export default login
