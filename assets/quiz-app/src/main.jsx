import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import './styles.css';

const mount = document.getElementById('wg-konfigurator-root');
if (mount) {
  const theme = mount.dataset.theme || 'dark';
  createRoot(mount).render(<App theme={theme} />);
}
