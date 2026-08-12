// archivo: src/app/services/canchas.service.ts
import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Cancha {
  id?: number;
  nombre: string;
  tipo: string;
  precio_hora?: number;
  cant_jugadores?: number;
}

@Injectable({
  providedIn: 'root',
})
export class CanchasService {
  private apiUrl = 'http://127.0.0.1:8000/api/canchas2';
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

  getCanchas(): Observable<Cancha[]> {
    return this.http.get<Cancha[]>(this.apiUrl);
  }

  getCancha(id: number): Observable<Cancha> {
    return this.http.get<Cancha>(`${this.apiUrl}/${id}`);
  }

  crearCancha(cancha: Cancha): Observable<Cancha> {
    return this.http.post<Cancha>(this.apiUrl, cancha, {
      headers: this.getAuthHeaders(),
    });
  }

  actualizarCancha(id: number, cancha: Cancha): Observable<Cancha> {
    return this.http.put<Cancha>(`${this.apiUrl}/${id}`, cancha, {
      headers: this.getAuthHeaders(),
    });
  }

  eliminarCancha(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`, {
      headers: this.getAuthHeaders(),
    });
  }
}
