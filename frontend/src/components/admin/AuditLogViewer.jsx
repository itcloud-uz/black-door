import React from 'react';

export default function AuditLogViewer({ auditLogs }) {
  return (
    <div>
      <h2 className="text-2xl font-black text-slate-100 mb-8">Tizim Audit Jurnali</h2>
      
      <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
        <h3 className="font-bold text-slate-200 text-sm mb-4">Foydalanuvchilar amallari tarixi (IP monitor)</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
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
                <tr key={log.id} className="border-b border-white/5 hover:bg-white/5">
                  <td className="py-3 px-4">
                    <strong className="text-slate-200">{log.user_name || 'Tizim'}</strong>
                    <div className="text-[10px] text-slate-400">{log.user_email}</div>
                  </td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold border ${
                      log.action === 'Created' || log.action === 'Login' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                      log.action === 'Deleted' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' : 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20'
                    }`}>{log.action}</span>
                  </td>
                  <td className="py-3 px-4 text-slate-300">{log.entity_type} ({log.entity_id})</td>
                  <td className="py-3 px-4 font-mono text-slate-300">{log.ip_address || '—'}</td>
                  <td className="py-3 px-4 text-xs font-mono text-slate-400 max-w-xs truncate">
                    {log.changes ? JSON.stringify(log.changes) : '—'}
                  </td>
                  <td className="py-3 px-4 text-slate-300">{new Date(log.created_at).toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
