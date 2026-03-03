import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class DashboardService {
  private apiUrl = 'http://127.0.0.1:8000/api';
  private tokenKey = 'access_token';

  constructor(private http: HttpClient) {}

  // 🔹 Generar headers con el token guardado
  private getAuthHeaders(): HttpHeaders {
    const token = localStorage.getItem(this.tokenKey);
    return token
      ? new HttpHeaders({ Authorization: `Bearer ${token}` })
      : new HttpHeaders();
  }

  // ========================
  // MÉTODOS DE CONSULTA API
  // ========================

  getMetrics(): Observable<{ ocupacion: number; reservas_activas: number }> {
    return this.http.get<{ ocupacion: number; reservas_activas: number }>(
      `${this.apiUrl}/reservas/metrics`,
      { headers: this.getAuthHeaders() }
    );
  }

  getReservasActivas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/reservas/activas`, {
      headers: this.getAuthHeaders(),
    });
  }

  // 🔹 Obtiene todos los usuarios registrados (requiere token válido)
  getUsuariosRegistrados(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/usuarios-registrados`, {
      headers: this.getAuthHeaders(),
    });
  }

  getReservasPendientes(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/reservas/pendientes`, {
      headers: this.getAuthHeaders(),
    });
  }

  getReservasCanceladas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/reservas/canceladas`, {
      headers: this.getAuthHeaders(),
    });
  }

  

  actualizarEstadoReserva(id: number, nuevoEstado: string): Observable<any> {
    return this.http.put(
      `${this.apiUrl}/reservas/${id}/estado`,
      { estado: nuevoEstado },
      { headers: this.getAuthHeaders() }
    );
  }

  getIngresosMensuales(): Observable<{ ingresos: number }> {
    return this.http.get<{ ingresos: number }>(
      `${this.apiUrl}/reservas/ingresos`,
      { headers: this.getAuthHeaders() }
    );
  }

  getAll(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/canchas2`);
  }

  borrarUsuario(id: number) {
  return this.http.delete<any>(
    `${this.apiUrl}/users/${id}`,
    { headers: this.getAuthHeaders() }
  );
}
actualizarUsuario(id: number, datos: any) {
  return this.http.put(
    `${this.apiUrl}/users/${id}`,
    datos,
    { headers: this.getAuthHeaders() }  // Agregar headers de autenticación
  );
}

}
