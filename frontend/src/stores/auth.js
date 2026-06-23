/**
 * Pinia auth store — Chapter 12.
 *
 * Identical to Chapter 11 except the register action passes through any
 * field-level validation errors returned by the hardened backend
 * (password must now be ≥ 8 characters; name, email rules are also stricter).
 *
 * Bare axios is used here (not api/client.js) to avoid a circular import:
 * api/client.js imports useAuth().
 */

import { defineStore } from 'pinia';
import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL;

export const useAuth = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user:  JSON.parse(localStorage.getItem('user') || 'null'),
  }),

  getters: {
    isAuthenticated: (s) => !!s.token,
    isAdmin:         (s) => s.user?.role === 'admin',
  },

  actions: {
    async login(email, password) {
      const { data } = await axios.post(`${baseURL}/auth/login`, { email, password });
      this.token = data.access_token;
      this.user  = data.user;
      localStorage.setItem('token', this.token);
      localStorage.setItem('user',  JSON.stringify(this.user));
    },

    async register(name, email, password) {
      // Throws on 400 (validation) or 409 (duplicate email); caller handles.
      await axios.post(`${baseURL}/auth/register`, { name, email, password });
      await this.login(email, password);
    },

    logout() {
      this.token = null;
      this.user  = null;
      localStorage.removeItem('token');
      localStorage.removeItem('user');
    },
  },
});
