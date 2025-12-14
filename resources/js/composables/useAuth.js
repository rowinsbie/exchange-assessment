import { ref } from 'vue';
import api from '../axios';

export const user = ref(null);      
export const loading = ref(false);   
export const error = ref(null);     

export function useAuth() {
    // Restore token from localStorage on load
    const token = localStorage.getItem('token');
    if (token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }

    const login = async (email, password) => {
        loading.value = true;
        error.value = null;

        try {
            const res = await api.post('/login', { email, password });

            localStorage.setItem('token', res.data.token);
            api.defaults.headers.common['Authorization'] = `Bearer ${res.data.token}`;

            user.value = res.data.user || { email };
            
            loading.value = false;
            return { success: true, data: res.data };
        } catch (err) {
            loading.value = false;
            error.value = err.response?.data?.message || 'Login failed';
            return { success: false, error: error.value };
        }
    };

    const logout = async () => {
        try {
            await api.post('/logout', {}, {
                headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
            });
        } catch (err) {
            console.error('Logout failed:', err);
        } finally {
            user.value = null;
            localStorage.removeItem('token');
            delete api.defaults.headers.common['Authorization'];
        }
    };

    const fetchUser = async () => {
        const token = localStorage.getItem('token');
        if (!token) return;

        try {
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            const res = await api.get('/profile');
            user.value = res.data;
        } catch {
            user.value = null;
            localStorage.removeItem('token');
            delete api.defaults.headers.common['Authorization'];
        }
    };

    return { user, loading, error, login, logout, fetchUser };
}
