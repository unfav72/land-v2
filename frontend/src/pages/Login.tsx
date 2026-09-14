import React from 'react';
import { useAuthStore, signInWithGoogle, logOut } from '../store/authStore';
import { signInWithGoogle as firebaseSignIn, logOut as firebaseLogOut } from '../lib/firebase';
import axios from 'axios';
import { Building2 } from 'lucide-react';

export default function Login() {
  const { setUser, setLoading, isLoading } = useAuthStore();
  const [error, setError] = React.useState('');

  const handleLogin = async () => {
    try {
      setLoading(true);
      setError('');
      
      // 1. Authenticate with Firebase
      const firebaseUser = await firebaseSignIn();
      const token = await firebaseUser.getIdToken();
      
      // 2. Verify with PHP Backend
      const response = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/auth/verify`, {
        idToken: token
      });
      
      const { user: backendUser } = response.data;
      
      // 3. Update global state
      setUser({
        id: backendUser.id,
        role: backendUser.role,
        displayName: firebaseUser.displayName || '',
        email: firebaseUser.email || '',
        firebaseUid: firebaseUser.uid
      }, token);

    } catch (err) {
      console.error(err);
      setError('Failed to log in. Please try again.');
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center text-blue-600">
          <Building2 size={48} />
        </div>
        <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Land Record Digitization
        </h2>
        <p className="mt-2 text-center text-sm text-gray-600">
          Officer Verification System
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
          <div className="mt-6">
            <button
              onClick={handleLogin}
              disabled={isLoading}
              className="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {isLoading ? 'Signing in...' : 'Sign in with Google'}
            </button>
            {error && (
              <p className="mt-2 text-center text-sm text-red-600">{error}</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
