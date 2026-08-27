import React from 'react';

export default function AuditLogViewer({ auditLogs }) {
  return (
    <div>
      <h2 className="text-2xl font-black text-[#2D3748] mb-8">Tizim Audit Jurnali</h2>
      
      <div className="skeuo-convex p-6">
        <h3 className="font-bold text-gray-800 text-sm mb-4">Foydalanuvchilar amallari tarixi (IP monitor)</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-gray-300 text-gray-500 uppercase font-extrabold tracking-wider">
                <th className="py-3 px-4">Foydalanuvchi</th>
                <th className="py-3 px-4">Amal</th>
                <th className="py-3 px-4">Kategoriya / Entity</th>
                <th className="py-3 px-4">IP manzil</th>
                <th className="py-3 px-4">O'zgarishlar (Changes)</th>
                <th className="py-3 px-4">Vaqti</th>
              </tr>
            </thead>
            <tbody>
              {auditLogs.map(log => (
                <tr key={log.id} className="border-b border-gray-200 hover:bg-gray-50">
                  <td className="py-3 px-4">
                    <strong className="text-gray-800">{log.user_name || 'Tizim'}</strong>
                    <div className="text-[10px] text-gray-400">{log.user_email}</div>
                  </td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                      log.action === 'Created' ? 'bg-green-100 text-green-800' :
                      log.action === 'Deleted' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'
                    }`}>{log.action}</span>
                  </td>
                  <td className="py-3 px-4">{log.entity_type} ({log.entity_id})</td>
                  <td className="py-3 px-4 font-mono text-gray-500">{log.ip_address || '—'}</td>
                  <td className="py-3 px-4 text-xs font-mono text-gray-400 max-w-xs truncate">
                    {log.changes ? JSON.stringify(log.changes) : '—'}
                  </td>
                  <td className="py-3 px-4">{new Date(log.created_at).toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
