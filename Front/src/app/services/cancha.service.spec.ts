import { TestBed } from '@angular/core/testing';

import { CanchasService } from './cancha.service';

describe('CanchaService', () => {
  let service: CanchasService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CanchasService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
