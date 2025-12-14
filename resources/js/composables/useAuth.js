import { ref } from 'vue';
import api from '../axios';

export const user = ref(null);

export function useAuth() {
    const login = async (email, password) => {
        await api.get('/sanctum/csrf-cookie');
        const res = await api.post('/login', { email, password });
        user.value = res.data.user || { email };
        return res;
    };

    const logout = async () => {
        await api.post('/logout');
        user.value = null;
    };

    return { user, login, logout };
}
