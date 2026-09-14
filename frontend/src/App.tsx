import { useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from './store/authStore';
import { auth } from './lib/firebase';
import { onAuthStateChanged } from 'firebase/auth';
import axios from 'axios';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Verification from './pages/Verification';

function App() {
  const { isAuthenticated, isLoading, setUser, setLoading } = useAuthStore();

  useEffect(() => {
    if (!auth) {
      console.warn("Auth not initialized. Showing login screen in demo mode.");
      setLoading(false);
      return;
    }
    
    const unsubscribe = onAuthStateChanged(auth, async (firebaseUser) => {
      if (firebaseUser) {
        try {
          const token = await firebaseUser.getIdToken();
          
          // In a real app we'd fetch the DB role again here if they just refreshed
          // For MVP, we set them up as officer to prevent logout loop if backend is mocked locally
          // Ideally: call /auth/verify again
          const response = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/auth/verify`, {
            idToken: token
          }).catch(() => null);

          let role: 'officer' | 'public' | 'admin' = 'public';
          let dbId = firebaseUser.uid;

          if (response?.data?.user) {
             role = response.data.user.role;
             dbId = response.data.user.id;
          }

          setUser({
            id: dbId,
            role: role,
            displayName: firebaseUser.displayName || '',
            email: firebaseUser.email || '',
            firebaseUid: firebaseUser.uid
          }, token);
        } catch (error) {
          console.error("Auth init error:", error);
          setUser(null, null);
        }
      } else {
        setUser(null, null);
      }
    });

    return () => unsubscribe();
  }, [setUser]);

  if (isLoading) {
    // Basic loading screen
    return <div className="min-h-screen flex items-center justify-center bg-slate-50 text-slate-500">Loading application...</div>;
  }

  return (
    <BrowserRouter>
      <Routes>
        <Route 
          path="/login" 
          element={!isAuthenticated ? <Login /> : <Navigate to="/dashboard" />} 
        />
        <Route 
          path="/dashboard" 
          element={isAuthenticated ? <Dashboard /> : <Navigate to="/login" />} 
        />
        <Route 
          path="/verify/:id" 
          element={isAuthenticated ? <Verification /> : <Navigate to="/login" />} 
        />
        <Route 
          path="*" 
          element={<Navigate to={isAuthenticated ? "/dashboard" : "/login"} />} 
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
