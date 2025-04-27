from fastapi import FastAPI, Depends, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy import create_engine, Column, Integer, String, Float, Date, func, text
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, Session
from typing import List, Optional
from datetime import date
import os
from dotenv import load_dotenv
from pydantic import BaseModel

# Carregar variáveis de ambiente
load_dotenv()

# Configuração do aplicativo FastAPI
app = FastAPI(
    title="Vehicle Rental API - Reports Service",
    description="Serviço de relatórios para o sistema de aluguel de veículos",
    version="1.0.0",
)

# Configuração de CORS
origins = os.getenv("ALLOW_ORIGINS", "http://localhost").split(",")
app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configuração de banco de dados
DB_CONNECTION = os.getenv("DB_CONNECTION", "mysql")
DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = os.getenv("DB_PORT", "3306")
DB_DATABASE = os.getenv("DB_DATABASE", "vehicle_rental")
DB_USERNAME = os.getenv("DB_USERNAME", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "password")

# Construir a URL de conexão com base no tipo de banco
if DB_CONNECTION == "mysql":
    SQLALCHEMY_DATABASE_URL = f"mysql+pymysql://{DB_USERNAME}:{DB_PASSWORD}@{DB_HOST}:{DB_PORT}/{DB_DATABASE}"
else:
    raise ValueError(f"Tipo de conexão de banco de dados não suportado: {DB_CONNECTION}")

# Criar engine SQLAlchemy
engine = create_engine(SQLALCHEMY_DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

# Modelos Pydantic para a API
class RevenueReport(BaseModel):
    plate: str
    make: str
    model: str
    total_rentals: int
    total_revenue: float

# Função para obter uma sessão de banco de dados
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

@app.get("/")
def read_root():
    """Endpoint raiz para verificar se o serviço está funcionando"""
    return {"status": "ok", "message": "Vehicle Rental API Reports Service"}

@app.get("/reports/revenue", response_model=List[RevenueReport])
def get_revenue_report(
    start: date = Query(..., description="Data inicial (YYYY-MM-DD)"),
    end: date = Query(..., description="Data final (YYYY-MM-DD)"),
    db: Session = Depends(get_db)
):
    """
    Gera um relatório de receita por veículo no intervalo de datas especificado.
    
    - **start**: Data inicial no formato YYYY-MM-DD
    - **end**: Data final no formato YYYY-MM-DD
    
    Retorna uma lista de veículos com informações de receita e quantidade de aluguéis.
    """
    if start > end:
        raise HTTPException(status_code=400, detail="A data inicial deve ser anterior à data final")
    
    # Consulta SQL que agrega os dados de aluguel por veículo
    query = text("""
        SELECT 
            v.plate, 
            v.make, 
            v.model, 
            COUNT(r.id) as total_rentals, 
            SUM(r.total_amount) as total_revenue
        FROM 
            rentals r
        JOIN 
            vehicles v ON r.vehicle_id = v.id
        WHERE 
            r.start_date >= :start_date
            AND r.end_date <= :end_date
            AND r.total_amount IS NOT NULL
        GROUP BY 
            v.id, v.plate, v.make, v.model
        ORDER BY 
            total_revenue DESC
    """)
    
    result = db.execute(
        query, 
        {"start_date": start, "end_date": end}
    ).fetchall()
    
    # Converter o resultado da consulta para o formato de resposta
    report_data = [
        {
            "plate": row[0],
            "make": row[1],
            "model": row[2],
            "total_rentals": row[3],
            "total_revenue": float(row[4]) if row[4] else 0.0
        }
        for row in result
    ]
    
    return report_data

if __name__ == "__main__":
    import uvicorn
    port = int(os.getenv("PORT", "3000"))
    uvicorn.run("app.main:app", host="0.0.0.0", port=port, reload=True) 