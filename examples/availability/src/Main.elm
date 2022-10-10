module Main exposing(main)

import Browser

--import Html exposing (..)
import Css as C
import Html as H
import Html.Styled exposing (..)
import Html.Styled.Attributes exposing (..)
import Html.Styled.Events exposing (onInput, onClick, on)
import Http
import Json.Decode as D
import Json.Encode as J
import Time
import Maybe
import Task
import Tuple
import Array
import Date exposing (Date, day, month, weekday, year, Interval(..))
import Time exposing (Weekday(..))
import DatePicker exposing (DateEvent(..), defaultSettings)

main =
  Browser.document
    { init = init
    , view = view
    , update = update
    , subscriptions = subscriptions
    }

-- MODEL

type alias Model =
    { current    : Page
    , monday     : Maybe Date.Date
    , datePicker : DatePicker.DatePicker
    , busyList   : List Interval
    , generated  : Maybe Generated
    , message    : String
    }

type alias Interval =
    { dow   : Dow
    , start : Hour
    , end   : Hour
    }
type Hour = Hour Int Int
hour : Hour -> Int
hour (Hour h _) = h
minute : Hour -> Int
minute (Hour _ m) = m
toString : Hour -> String
toString (Hour h m) =
    (String.fromInt h) ++ ":" ++ (if m < 10 then "0" else "") ++ (String.fromInt m)
type alias Slot =
    { dow  : Dow
    , hour : Hour
    }
    
type Page = SelectWeek
          | MainPage
    
init : () -> (Model, Cmd Msg)
init _ =
    let
        (datePicker, datePickerFx) = DatePicker.init
    in
        ( { current    = SelectWeek
          , monday     = Nothing
          , datePicker = datePicker
          , busyList   = []
          , generated  = Nothing
          , message    = "" 
          }
        , Cmd.map ToDatePicker datePickerFx
        )
        
-- SUBSCRIPTIONS

subscriptions : Model -> Sub Msg
subscriptions model = Sub.none

-- UPDATE

type Msg = ToDatePicker DatePicker.Msg
         | ToggleSlot Slot
         | FetchedGeneration (Result Http.Error (ApiResult Generated))
type alias Generated =
    { text : String
    , sentences : List Sentence
    }
type alias Sentence =
    { start : Int
    , end : Int
    , blocks : List Block
    }
type alias Block =
    { dows   : List Int
    , start  : Hour
    , end    : Hour
    , free   : Bool
    , purity : Float
    }
type alias Dow = Int
type Range = Range Dow Hour Hour
dowRanges = List.range 0 5
dayStart = (Hour 9 0)
dayEnd = (Hour 17 0)
fullRanges = List.map (\dow-> Range dow dayStart dayEnd) dowRanges

settings : DatePicker.Settings
settings =
    let
        isDisabled date =
            [ Sat, Sun ]
                |> List.member (weekday date)
    in
        { defaultSettings | isDisabled = isDisabled }  

update : Msg -> Model -> (Model, Cmd Msg)
update msg model =
    let
        coalesceHttpError : Result Http.Error (ApiResult a) -> (a -> (Model, Cmd Msg) ) -> (Model, Cmd Msg)
        coalesceHttpError x f =
            case x of
                Ok (Ok v) -> f v
                Ok (Err s) -> ( { model | message = s },  Cmd.none )
                Err e -> ( { model | message = (toStr e) },  Cmd.none ) 
    in 
        case msg of
            ToDatePicker subMsg ->
                let
                    (newDatePicker, dateEvent) = DatePicker.update settings subMsg model.datePicker
                    (newDate, newPage) =
                        case dateEvent of
                            Picked changedDate -> (Just changedDate, MainPage)
                            _ -> (Nothing, SelectWeek)
                in
                    ( { model
                          | current = newPage
                          , monday = Maybe.map (\d -> Date.floor Monday d) newDate
                          , datePicker = newDatePicker
                      }
                    , Cmd.none
                    )
            FetchedGeneration v ->
                coalesceHttpError v (\g -> ( { model | generated = Just g }, Cmd.none ))
            ToggleSlot slot ->
                let
                    available = List.filter (\i -> i.start == slot.hour && i.dow == slot.dow) model.busyList |> List.isEmpty
                    end = if minute slot.hour == 0 then Hour (hour slot.hour) 30 else Hour ((hour slot.hour)+1) 0
                    newBusyList = if available then model.busyList ++ [ { dow = slot.dow
                                                                        , start = slot.hour
                                                                        , end = end
                                                                        } ]
                                  else
                                      List.filter (\i -> i.start /= slot.hour) model.busyList
                in
                    ( { model | busyList = newBusyList }, fetchGeneration model.busyList )
      
toStr : Http.Error -> String
toStr e =
    case e of
        Http.BadUrl s -> "Bad URL " ++ s
        Http.Timeout -> "Timeout"
        Http.NetworkError -> "Network error"
        Http.BadStatus s -> "Bad status: " ++ (String.fromInt s)
        Http.BadBody s -> "Bad body: " ++ s

fetchGeneration : List Interval -> Cmd Msg
fetchGeneration busyList =
    let
        h2j : Hour -> J.Value
        h2j (Hour h m) = J.list J.int [ h, m ]
    in
        Http.post
            { url = "/api.php"
            , body = Http.jsonBody 
                     (J.list (\i->i)
                          [ J.int 1
                          , J.object (List.map (\r ->
                                                   case r of
                                                       Range dow h1 h2 ->
                                                          ( String.fromInt dow, J.list (\x->x) [ h2j h1, h2j h2 ])) fullRanges)
                          , J.list (\i -> J.list (\x->x) [ J.int i.dow, h2j i.start, h2j i.end ]) busyList ])
            , expect = Http.expectJson FetchedGeneration (apiDecode decodeGenerated)
            }
        
type alias ApiResult a = Result String a
apiDecode : D.Decoder a -> D.Decoder (ApiResult a)
apiDecode d =
    D.andThen (\status->
                   if status == 200 then
                       (D.map Ok d)
                   else
                       (D.map Err (D.field "error" D.string)))
        (D.field "status" D.int)

decodeGenerated : D.Decoder Generated
decodeGenerated = D.map2 Generated
                  (D.field "text" D.string)
                  (D.field "sentences" (D.list decodeSentence))

decodeSentence : D.Decoder Sentence
decodeSentence = D.map3 Sentence
                  (D.field "offsetStart" D.int)
                  (D.field "offsetEnd" D.int)
                  (D.field "blocks" (D.list decodeBlock))
                      
decodeBlock : D.Decoder Block
decodeBlock = D.map5 Block
                  (D.field "dows" (D.list D.int))
                  (D.field "startTime" decodeHour)
                  (D.field "endTime" decodeHour)
                  (D.field "isFree" D.bool)
                  (D.field "purity" D.float)
                 
decodeHour : D.Decoder Hour
decodeHour = D.map2 Hour
             (D.index 0 D.int)
             (D.index 1 D.int)


-- VIEW

view : Model -> Browser.Document Msg
view model =
    { title = "PHP-NLGen AvailabilityGenerator Demo"
    , body = [ viewHtml model |> toUnstyled ]
    }
viewHtml : Model -> Html Msg
viewHtml model =
    div [ css centeredLayout ]
        ([ h1 [ css [ C.textAlign C.center ] ] [ text "PHP-NLGen AvailabilityGenerator Demo" ]
        , p [] [ text model.message ]
        ] ++
        (case model.current of
           SelectWeek ->
             [ DatePicker.view model.monday settings model.datePicker |> Html.Styled.fromUnstyled |> Html.Styled.map ToDatePicker  ]
           MainPage ->
             [ p [ css [ C.textAlign C.center ] ] [ text "Click on the cells to toggle availability" ]
             , case model.monday of
                 Just date -> h1 [] [ text <| Date.format "MMM d, yyyy" date ]
                 _ -> p [] [ text "No date selected" ]
             , table [ css [ C.width (C.pc 50) ] ]
                   ([ tr []
                         ([td [] [ text "Hour"]] ++ (List.map (\dow -> td [] [ text <| String.fromInt (dow+1) ]) dowRanges))
                    ] ++ (List.map
                             (\segment ->
                                  let
                                      hs = hour dayStart
                                      h = hs + (segment // 2)
                                      m = if remainderBy 2 segment == 1 then 30 else 0
                                      hh = Hour h m
                                      available dow  = List.filter (\i -> i.start == hh && i.dow == dow) model.busyList |> List.isEmpty
                                  in
                                      (tr []
                                           ([td [] [ text (toString hh) ]] ++
                                           (List.map ( \dow -> td [ onClick (ToggleSlot { dow = dow, hour = hh }) ]
                                                          [ text (if available dow then "_" else "X") ]) dowRanges))))
                             (List.range 0 ((hour dayEnd - hour dayStart) * 2))))
             , div [] [
                    text <| Maybe.withDefault "" (Maybe.map (\g->g.text) model.generated) ]
             ]))

centeredLayout : List C.Style
centeredLayout =
   [ C.justifyContent C.center
   , C.alignItems C.center
   , C.verticalAlign C.center
   , C.width (C.pc 80)
   , C.margin C.auto
   ]

    
