import React from 'react';
import { useAuthStore } from '../store/authStore';
import { logOut as firebaseLogOut } from '../lib/firebase';
import { LogOut, FileText, CheckCircle, AlertCircle, FileSearch } from 'lucide-react';

export default function Dashboard() {
  const { user, logout } = useAuthStore();

  const handleLogout = async () => {
    await firebaseLogOut();
    logout();
  };

  if (user?.role !== 'officer' && user?.role !== 'admin') {
    return (
      <div className="p-8 text-center">
        <h1 className="text-2xl font-bold mb-4">Public Portal</h1>
        <p>Welcome! You can submit digitization requests here.</p>
        <button onClick={handleLogout} className="mt-4 text-blue-600 underline">Logout</button>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      {/* Navbar */}
      <nav className="bg-white shadow-sm border-b border-slate-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-bold text-slate-800">Officer Dashboard</h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-slate-600">{user.displayName}</span>
              <button 
                onClick={handleLogout}
                className="p-2 text-slate-500 hover:text-slate-700 transition-colors"
                title="Logout"
              >
                <LogOut size={20} />
              </button>
            </div>
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {/* Stats Grid */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <StatCard title="Total Documents" count="128" icon={<FileText />} color="text-blue-600" bg="bg-blue-100" />
          <StatCard title="Awaiting Review" count="14" icon={<AlertCircle />} color="text-yellow-600" bg="bg-yellow-100" />
          <StatCard title="Processing" count="5" icon={<FileSearch />} color="text-indigo-600" bg="bg-indigo-100" />
          <StatCard title="Verified" count="109" icon={<CheckCircle />} color="text-green-600" bg="bg-green-100" />
        </div>

        {/* Action Area */}
        <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-lg font-semibold text-slate-800">Recent Documents</h2>
            <button className="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition-colors">
              Upload New Document
            </button>
          </div>
          
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left text-slate-500">
              <thead className="text-xs text-slate-700 uppercase bg-slate-50">
                <tr>
                  <th className="px-4 py-3">Document ID</th>
                  <th className="px-4 py-3">Date</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr className="border-b">
                  <td className="px-4 py-3 font-medium text-slate-900">doc-12345</td>
                  <td className="px-4 py-3">2026-09-13</td>
                  <td className="px-4 py-3">
                    <span className="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">Review Required</span>
                  </td>
                  <td className="px-4 py-3">
                    <button 
                      onClick={() => window.location.href = '/verify/doc-12345'}
                      className="text-blue-600 hover:underline font-medium"
                    >
                      Verify Now
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>
  );
}

function StatCard({ title, count, icon, color, bg }: { title: string, count: string, icon: React.ReactNode, color: string, bg: string }) {
  return (
    <div className="bg-white overflow-hidden shadow-sm rounded-lg border border-slate-200">
      <div className="p-5">
        <div className="flex items-center">
          <div className="flex-shrink-0">
            <div className={`p-3 rounded-md ${bg} ${color}`}>
              {icon}
            </div>
          </div>
          <div className="ml-5 w-0 flex-1">
            <dl>
              <dt className="text-sm font-medium text-slate-500 truncate">{title}</dt>
              <dd>
                <div className="text-2xl font-bold text-slate-900">{count}</div>
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>
  );
}
