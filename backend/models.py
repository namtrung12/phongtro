from datetime import datetime

from sqlalchemy import Column, Integer, String, Text, Boolean, DateTime

from .database import Base


class Room(Base):
    __tablename__ = "rooms"

    id = Column(Integer, primary_key=True, index=True)
    title = Column(String(255), nullable=False)
    price = Column(Integer, nullable=False)
    lead_price_expect = Column(Integer, nullable=True)
    lead_price_suggest = Column(Integer, nullable=True)
    area = Column(String(120), nullable=False)
    address = Column(String(255), nullable=False)
    description = Column(Text, nullable=True)
    thumbnail = Column(String(255), nullable=True)
    video_url = Column(String(255), nullable=True)
    shared_owner = Column(Boolean, default=False)
    closed_room = Column(Boolean, default=False)
    status = Column(String(32), default="pending", nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow)

    def __repr__(self) -> str:
        return f"<Room id={self.id} title={self.title!r}>"
