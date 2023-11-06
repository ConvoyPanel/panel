import http from '@/api/http'

export interface LoginData {
    email: string
    password: string
}

export default ({ email, password }: LoginData): Promise<any> => {
    return http.post('/login', {
        email,
        password,
    })
}