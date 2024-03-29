module Main exposing(main)

import Browser
import Http
import Json.Decode as D
import Json.Encode as J
import Maybe
import Array
import Time exposing (Weekday(..))
import Css as C
import Html.Styled exposing (..)
import Html.Styled.Attributes exposing (..)
import Html.Styled.Events exposing (onMouseOver, onMouseOut, onClick)
import Date exposing (Date, day, month, weekday, year, Interval(..))

main =
  Browser.document
    { init = init
    , view = view
    , update = update
    , subscriptions = subscriptions
    }

-- MODEL

type alias Model =
    { busyList   : List Interval
    , coarseness : Coarseness
    , generated  : Maybe Generated
    , highlight  : Maybe Int
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
    
init : () -> (Model, Cmd Msg)
init _ =
        ( { busyList   = []
          , generated  = Nothing
          , coarseness = 1
          , highlight  = Nothing
          , message    = ""
          }
        , fetchGeneration 1 []
        )
        
-- SUBSCRIPTIONS

subscriptions : Model -> Sub Msg
subscriptions model = Sub.none

-- UPDATE

type Msg = ToggleSlot Slot
         | SelectCoarseness Coarseness
         | HighlightIn Int
         | HighlightOut
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
type alias Coarseness = Int
type alias Dow = Int
type Range = Range Dow Hour Hour
dowRanges = List.range 0 5
dayStart = (Hour 9 0)
dayEnd = (Hour 17 0)
fullRanges = List.map (\dow-> Range dow dayStart dayEnd) dowRanges
dowNames = Array.fromList [ "M", "T", "W", "T", "F", "S" ]
coarseNames = Array.fromList [ "SUCCINCT", "BASE", "SPECIFIC", "EXACT" ]

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
            FetchedGeneration v ->
                coalesceHttpError v (\g -> ( { model
                                                 | message = ""
                                                 , generated = Just g }, Cmd.none ))
            SelectCoarseness coarseness ->
                    ( { model | coarseness = coarseness }, fetchGeneration coarseness model.busyList )
            HighlightIn h ->
                    ( { model | highlight = Just h }, Cmd.none )
            HighlightOut ->
                    ( { model | highlight = Nothing }, Cmd.none )
            ToggleSlot slot ->
                let
                    available = List.filter (\i -> i.start == slot.hour && i.dow == slot.dow) model.busyList |> List.isEmpty
                    end = if minute slot.hour == 0 then Hour (hour slot.hour) 30 else Hour ((hour slot.hour)+1) 0
                    newBusyList = if available then
                                      model.busyList ++ [ { dow = slot.dow
                                                          , start = slot.hour
                                                          , end = end
                                                          } ]
                                  else
                                      List.filter (\i -> i.start /= slot.hour || i.dow /= slot.dow) model.busyList
                in
                    ( { model | busyList = newBusyList }, fetchGeneration model.coarseness newBusyList )
      
toStr : Http.Error -> String
toStr e =
    case e of
        Http.BadUrl s -> "Bad URL " ++ s
        Http.Timeout -> "Timeout"
        Http.NetworkError -> "Network error"
        Http.BadStatus s -> "Bad status: " ++ (String.fromInt s)
        Http.BadBody s -> "Bad body: " ++ s

fetchGeneration : Coarseness -> List Interval -> Cmd Msg
fetchGeneration coarseness busyList =
    let
        h2j : Hour -> J.Value
        h2j (Hour h m) = J.list J.int [ h, m ]
    in
        Http.post
            { url = "api.php"
            , body = Http.jsonBody 
                     (J.list (\i->i)
                          [ J.int coarseness
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
    div [ css [ C.textAlign C.center ] ]
        ([ h1 [ css [ C.textAlign C.center ] ] [ text "PHP-NLGen AvailabilityGenerator Demo" ]
         , p [] [ text model.message ]
         , p [ css [ C.textAlign C.center ] ] [ text "Click on the cells to toggle availability. See below for the generated text." ]
         , p [ css [ C.textAlign C.center ] ]
             ([ text "Coarse level (click to select): " ] ++
                  (List.concat <| List.map (\(idx,str) ->
                                                [ span
                                                      (if idx == model.coarseness then
                                                           [ css [ C.padding (C.px 5)]]
                                                       else
                                                           [ css [ C.padding (C.px 5), C.backgroundColor (C.hex "A0A0A0") ], onClick (SelectCoarseness idx) ])
                                                      [ text str ]
                                                , span [] [text " "]]) (Array.toIndexedList coarseNames)))
         ,  table [ css [ C.verticalAlign C.center ] ]
             ([ tr []
                    ([td [css [ C.width (C.px 50)]] [ text "Hour"]] ++
                         (List.map
                              (\dow -> td [ css [ C.width (C.px 20) ] ] [ Array.get dow dowNames |> Maybe.withDefault "?" |> text ])
                              dowRanges))
              ] ++ (List.map
                        (\segment ->
                             let
                                 hs = hour dayStart
                                 h = hs + (segment // 2)
                                 odd = remainderBy 2 segment == 1
                                 m = if odd then 30 else 0
                                 hh = Hour h m
                                 blocks = case (model.highlight, model.generated) of
                                              (Just i, Just g) -> Array.get i (Array.fromList g.sentences) |> Maybe.map (\s -> s.blocks) |> Maybe.withDefault []
                                              _ -> []
                             in
                                 (tr (if odd then [css [C.backgroundColor (C.hex "C0F0C0")]] else [])
                                      ([td [] [ text (toString hh) ]] ++
                                           (List.map ( \dow ->
                                                           let
                                                               available  = List.filter (\i -> i.start == hh && i.dow == dow) model.busyList |> List.isEmpty
                                                               (inblock, purity) = List.filterMap (\b ->
                                                                                                       let (inb, pu) = inBlock b dow hh
                                                                                                       in if inb then Just (inb,pu) else Nothing) blocks
                                                                                   |> List.head |> Maybe.withDefault (False, 0.0)
                                                               blockBg = C.rgb 200 200 (Basics.floor purity * 255)
                                                           in 
                                                           td [ onClick (ToggleSlot { dow = dow, hour = hh }), css ([ C.width (C.px 20) ]
                                                                                                                        ++ (if inblock then [ C.backgroundColor blockBg ] else [])) ]
                                                           [ text (if available then "_" else "X") ]) dowRanges))))
                        (List.range 0 ((hour dayEnd - hour dayStart) * 2))))
         , div []
             (case model.generated of
                  Just g -> ([ p [ css [ C.textAlign C.center ] ] [ text "Mouse over to connect the text to the table." ] ] ++
                                 List.concatMap (\(idx,sent)->
                                                     let
                                                         selected = case model.highlight of
                                                                        Just h -> idx == h
                                                                        _ -> False
                                                         even = remainderBy 2 idx == 0
                                                         out = onMouseOut HighlightOut
                                                         evts = [ onMouseOver (HighlightIn idx), out ]
                                                     in
                                                         [ span (case (selected, even) of
                                                                     (True, _) -> [css [C.backgroundColor (C.hex "D0D0FF")], out]
                                                                     (_, True) -> [css [C.backgroundColor (C.hex "D0D0D0")]] ++ evts
                                                                     _ -> evts)
                                                               [ text <| String.slice sent.start sent.end g.text ], span [] [text " "]] )
                                 (List.indexedMap Tuple.pair g.sentences))
                  _ -> [])
         , div [] [ p [] [ text "Source code: "
                         , a [ href "https://github.com/DrDub/php-nlgen/blob/master/src/Grammars/Availability/AvailabilityGrammar.php" ][ text "Grammar" ]
                         , span [] [ text " "]
                         , a [ href "https://github.com/DrDub/php-nlgen/tree/master/examples/availability" ] [ text "Demo" ]]]
         ])

centeredLayout : List C.Style
centeredLayout =
   [ C.justifyContent C.center
   , C.alignItems C.center
   , C.verticalAlign C.center
   , C.width (C.pc 80)
   , C.margin C.auto
   ]

inBlock : Block -> Dow -> Hour -> (Bool, Float)
inBlock { dows, start, end, free, purity } dow (Hour xh xm) =
    if List.member dow dows then
        case (start, end) of
            (Hour sh sm, Hour eh em) ->
                if (sh < xh || (sh == xh && sm <= xm)) &&
                    ( xh < eh || (xh == eh && xm < em)) then
                    (True, purity)
                else
                    (False, 0.0)
    else
        (False, 0.0)
