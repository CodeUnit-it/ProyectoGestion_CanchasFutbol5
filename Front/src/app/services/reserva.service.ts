import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class ReservaService {
  getDisponibilidadMes(mes: string, canchaId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/disponibilidad-mes`, {
      params: { mes, canchaId },
    });
  }
  private apiUrl = 'http://127.0.0.1:8000/api';
  private tokenKey = 'access_token';

  constructor(private http: HttpClient) {}

  private getAuthHeaders(): HttpHeaders {
    const token = localStorage.getItem(this.tokenKey);

    if (token) {
      return new HttpHeaders({
        Authorization: `Bearer ${token}`,
      });
    }
    return new HttpHeaders();
  }
  getReservasActivas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/reservas/activas`, {
      headers: this.getAuthHeaders(),
    });
  }

  getReservasUsuario(userId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/reservations`, {
      params: { user_id: userId },
      headers: this.getAuthHeaders(),
    });
  }

  getCanchas(): Observable<any> {
    return this.http.get(`${this.apiUrl}/canchas2`);
  }

  getReservas(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/reservas`, {
      headers: this.getAuthHeaders(),
    });
  }

  getHorarios(fecha: string, canchaId: number) {
    return this.http.get<string[]>(`${this.apiUrl}/horarios`, {
      params: { fecha, canchaId },
    });
  }

  crearReserva(reserva: any): Observable<any> {
    const token = localStorage.getItem('access_token');

    if (!token) {
      return throwError(() => new Error('Usuario no autenticado'));
    }

    const headers = new HttpHeaders({
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    });

    return this.http.post(`${this.apiUrl}/reservas`, reserva, { headers });
  }

  getDisponibilidad(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/disponibilidad`);
  }

  getUsuariosClientes() {
    return this.http.get<any[]>(`${this.apiUrl}/usuarios-registrados`, {
      headers: this.getAuthHeaders(),
    });
  }
actualizarReserva(id: number, data: any) {
  return this.http.put(
    `${this.apiUrl}/reservas/${id}`,
    data,
    { headers: this.getAuthHeaders() }
  );
}

eliminarReserva(id: number) {
  return this.http.delete(
    `${this.apiUrl}/reservas/${id}`,
    { headers: this.getAuthHeaders() }
  );
}

}
