import { create } from 'zustand';

interface User {
  id: string;
  role: 'officer' | 'public' | 'admin';
  displayName?: string;
  email?: string;
  firebaseUid?: string;
}

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  token: string | null;
  isLoading: boolean;
  setUser: (user: User | null, token: string | null) => void;
  setLoading: (isLoading: boolean) => void;
  logout: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  user: null,
  isAuthenticated: false,
  token: null,
  isLoading: true,
  setUser: (user, token) => set({ user, token, isAuthenticated: !!user, isLoading: false }),
  setLoading: (isLoading) => set({ isLoading }),
  logout: () => set({ user: null, token: null, isAuthenticated: false, isLoading: false }),
}));
