from fastapi import FastAPI, Depends, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from sqlalchemy.orm import Session

from . import models, schemas
from .database import Base, engine, get_db
from .utils import suggest_lead_price
from .config import settings

# Create tables at startup (simple dev path; replace with migrations in prod)
Base.metadata.create_all(bind=engine)

app = FastAPI(title="PhongTro API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.cors_origins if settings.cors_origins != ["*"] else ["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/healthz")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.post("/api/v1/rooms", response_model=schemas.RoomRead, status_code=status.HTTP_201_CREATED)
def create_room(room_in: schemas.RoomCreate, db: Session = Depends(get_db)):
    lead_price_suggest = suggest_lead_price(room_in.price)
    lead_price_expect = room_in.lead_price_expect or lead_price_suggest
    room = models.Room(
        title=room_in.title.strip(),
        price=room_in.price,
        lead_price_expect=lead_price_expect,
        lead_price_suggest=lead_price_suggest,
        area=room_in.area.strip(),
        address=room_in.address.strip(),
        description=room_in.description,
        thumbnail=str(room_in.thumbnail) if room_in.thumbnail else None,
        video_url=str(room_in.video_url) if room_in.video_url else None,
        shared_owner=room_in.shared_owner,
        closed_room=room_in.closed_room,
        status="pending",
    )
    db.add(room)
    db.commit()
    db.refresh(room)
    return room


@app.get("/api/v1/rooms", response_model=list[schemas.RoomRead])
def list_rooms(db: Session = Depends(get_db)):
    rooms = db.query(models.Room).order_by(models.Room.created_at.desc()).all()
    return rooms
