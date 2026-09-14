import React, { useState } from 'react';
import { FileCheck, FileX, AlertTriangle, Eye, ArrowLeft, CheckCircle2 } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

// Mock data for MVP demonstration
const mockExtractedData = {
  documentId: "doc-12345",
  fields: [
    { name: "owner_name", label: "Owner Name", aiValue: "Ramesh Kumar", confidence: 92, requiresReview: false },
    { name: "survey_number", label: "Survey Number", aiValue: "124/38A", confidence: 65, requiresReview: true },
    { name: "area", label: "Area (Sq. Ft.)", aiValue: "2400", confidence: 88, requiresReview: false },
    { name: "village", label: "Village", aiValue: "Sample Village", confidence: 95, requiresReview: false }
  ]
};

export default function Verification() {
  const navigate = useNavigate();
  const [fields, setFields] = useState(mockExtractedData.fields.map(f => ({
    ...f,
    officerValue: f.aiValue,
    isCorrected: false
  })));

  const handleCorrection = (index: number, newValue: string) => {
    const newFields = [...fields];
    newFields[index].officerValue = newValue;
    newFields[index].isCorrected = newValue !== newFields[index].aiValue;
    setFields(newFields);
  };

  const handleApprove = () => {
    alert("Record Approved and Verified!");
    navigate('/dashboard');
  };

  return (
    <div className="min-h-screen bg-slate-50 flex flex-col">
      {/* Header */}
      <header className="bg-white border-b border-slate-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <div className="flex items-center space-x-4">
          <button onClick={() => navigate('/dashboard')} className="text-slate-500 hover:text-slate-800">
            <ArrowLeft size={20} />
          </button>
          <h1 className="text-xl font-bold text-slate-800">Record Verification</h1>
          <span className="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-0.5 rounded">
            Under Review
          </span>
        </div>
        <div className="space-x-3 flex">
          <button className="flex items-center space-x-2 px-4 py-2 bg-red-50 text-red-600 rounded-md hover:bg-red-100 transition">
            <FileX size={18} />
            <span>Reject</span>
          </button>
          <button 
            onClick={handleApprove}
            className="flex items-center space-x-2 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition shadow-sm"
          >
            <CheckCircle2 size={18} />
            <span>Verify & Approve</span>
          </button>
        </div>
      </header>

      {/* Main Split Screen */}
      <div className="flex-1 flex overflow-hidden">
        
        {/* Left: Original Document */}
        <div className="w-1/2 border-r border-slate-200 bg-slate-100 p-6 flex flex-col">
          <h2 className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center">
            <Eye size={16} className="mr-2" /> Original Document
          </h2>
          <div className="flex-1 bg-white border border-slate-200 rounded-lg shadow-inner flex items-center justify-center text-slate-400 overflow-hidden relative">
            {/* Placeholder for PDF/Image viewer */}
            <div className="text-center p-8">
               <p className="mb-2 font-medium">Document Viewer</p>
               <p className="text-sm">In production, the uploaded document image or PDF is rendered here.</p>
               <div className="mt-8 border-4 border-dashed border-slate-200 w-64 h-80 mx-auto rounded-lg flex items-center justify-center">
                 <span className="text-slate-300 font-bold text-2xl">PDF / JPG</span>
               </div>
            </div>
          </div>
        </div>

        {/* Right: AI Extraction & Correction */}
        <div className="w-1/2 bg-white p-6 overflow-y-auto">
           <h2 className="text-sm font-bold text-slate-500 uppercase tracking-wider mb-6 flex items-center">
             AI Extracted Data
           </h2>
           
           <div className="space-y-6">
             {fields.map((field, idx) => (
               <div key={idx} className={`p-4 rounded-lg border ${field.requiresReview ? 'border-yellow-300 bg-yellow-50' : 'border-slate-200 bg-slate-50'}`}>
                 <div className="flex justify-between mb-2">
                   <label className="text-sm font-semibold text-slate-700">{field.label}</label>
                   <div className="flex items-center space-x-3">
                     {field.requiresReview && (
                       <span className="flex items-center text-xs text-yellow-700 font-medium bg-yellow-200 px-2 py-1 rounded">
                         <AlertTriangle size={12} className="mr-1" /> Review Required
                       </span>
                     )}
                     <span className={`text-xs font-bold ${field.confidence > 85 ? 'text-green-600' : 'text-orange-500'}`}>
                       {field.confidence}% Confidence
                     </span>
                   </div>
                 </div>
                 
                 <div className="flex flex-col space-y-2">
                   <div className="text-xs text-slate-500 flex justify-between">
                     <span>AI Original: <strong className="text-slate-700">{field.aiValue}</strong></span>
                   </div>
                   <input 
                     type="text" 
                     value={field.officerValue}
                     onChange={(e) => handleCorrection(idx, e.target.value)}
                     className={`w-full p-2 border rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 outline-none ${field.isCorrected ? 'border-blue-400 bg-blue-50' : 'border-slate-300'}`}
                   />
                 </div>
               </div>
             ))}
           </div>
        </div>

      </div>
    </div>
  );
}
