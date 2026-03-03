import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, BehaviorSubject } from 'rxjs';
import { tap } from 'rxjs/operators';
import { HttpHeaders } from '@angular/common/http';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private apiUrl = 'http://127.0.0.1:8000/api';
  private loggedIn = new BehaviorSubject<boolean>(!!this.getToken());
  loggedIn$ = this.loggedIn.asObservable();

  private userRole = new BehaviorSubject<string>(
    localStorage.getItem('user_role') || ''
  );
  currentUserRole$ = this.userRole.asObservable();

  constructor(private http: HttpClient) {}

  login(credentials: { email: string; password: string }): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/login`, credentials).pipe(
      tap((res: any) => {
        this.saveToken(res.access_token); // ✅ Coincide con tu backend
        localStorage.setItem('user_role', res.user.role);
        
        this.userRole.next(res.user.role);
        localStorage.setItem('usuario', JSON.stringify(res.user));
      })
    );
  }

  saveToken(token: string): void {
    localStorage.setItem('access_token', token);
    this.loggedIn.next(true);
  }

  getToken(): string | null {
    return localStorage.getItem('access_token');
  }

  logout(): void {
    localStorage.removeItem('access_token');
    localStorage.removeItem('user_role'); // <--- limpiar rol
    localStorage.removeItem('usuario'); // ✅ limpiar usuario guardado

    this.loggedIn.next(false);
    this.userRole.next('');
  }

  isLoggedIn(): boolean {
    return !!this.getToken();
  }

 getUsuario(): {
  id: number;
  name: string;
  email: string;
  role: string;
  created_at: string; // agregado: fecha de registro
  telefono: number
} | null {
  const usuario = localStorage.getItem('usuario');
  return usuario ? JSON.parse(usuario) : null;
}

actualizarTelefono(id: number, telefono: string): Observable<any> {
  const token = this.getToken();
  const headers = new HttpHeaders({
    'Authorization': `Bearer ${token}`
  });

  return this.http.put(`${this.apiUrl}/usuarios/${id}`, { telefono }, { headers });
}

}
