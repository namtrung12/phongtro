from pydantic import BaseModel, Field, HttpUrl
from typing import Optional


class RoomBase(BaseModel):
    title: str = Field(..., max_length=255)
    price: int = Field(..., gt=0)
    area: str = Field(..., max_length=120)
    address: str = Field(..., max_length=255)
    description: Optional[str] = Field(None, max_length=4000)
    thumbnail: Optional[HttpUrl] = None
    video_url: Optional[HttpUrl] = None
    lead_price_expect: Optional[int] = Field(None, gt=0)
    shared_owner: bool = False
    closed_room: bool = False


class RoomCreate(RoomBase):
    pass


class RoomRead(RoomBase):
    id: int
    lead_price_suggest: Optional[int] = None
    status: str

    class Config:
        orm_mode = True
