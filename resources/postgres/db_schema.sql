CREATE TABLE public.text_detect_results (
    id integer NOT NULL,
    img_hash character varying(255) NOT NULL,
    img_url character varying(4096) NOT NULL,
    detected_data json NOT NULL
);

CREATE SEQUENCE public.text_detect_results_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

ALTER SEQUENCE public.text_detect_results_id_seq OWNED BY public.text_detect_results.id;

ALTER TABLE ONLY public.text_detect_results ALTER COLUMN id SET DEFAULT nextval('public.text_detect_results_id_seq'::regclass);

ALTER TABLE ONLY public.text_detect_results
    ADD CONSTRAINT text_detect_results_pkey PRIMARY KEY (id);

CREATE UNIQUE INDEX uniq_822f23ffba364945 ON public.text_detect_results USING btree (img_hash);